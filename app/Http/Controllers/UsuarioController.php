<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\BitacoraService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * CU04 Gestionar usuarios, CU05 Asignar rol a usuario y CU06 Restablecer acceso de usuario.
 * Solo para el administrador (middleware rol:administrador en las rutas).
 * Nunca se borran usuarios de la base (RNF-06): "eliminar" significa inhabilitar.
 */
class UsuarioController extends Controller
{
    // Columnas de datos personales que se guardan al crear y al modificar.
    private const CAMPOS_DATOS = [
        'nombres', 'apellidos', 'ci', 'matricula_profesional',
        'especialidad_id', 'correo', 'telefono', 'usuario',
    ];

    // MOSTRAR la lista de usuarios, con buscador opcional (CU04 paso 2).
    public function index(Request $request)
    {
        $request->validate(
            ['buscar' => ['nullable', 'string', 'max:100']],
            ['buscar.max' => 'El texto de búsqueda no puede superar los 100 caracteres.']
        );

        $buscar = $request->input('buscar');

        $usuarios = User::with('especialidad')
            // Si hay texto de búsqueda, se filtra por nombres, apellidos, usuario o CI.
            // Los orWhere van agrupados dentro de un where(function ...) para que
            // formen un solo bloque entre paréntesis en la consulta.
            ->when($buscar, function ($consulta, $buscar) {
                $consulta->where(function ($grupo) use ($buscar) {
                    $grupo->where('nombres', 'like', "%{$buscar}%")
                        ->orWhere('apellidos', 'like', "%{$buscar}%")
                        ->orWhere('usuario', 'like', "%{$buscar}%")
                        ->orWhere('ci', 'like', "%{$buscar}%");
                });
            })
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->paginate(10)
            // Conserva ?buscar=... en los enlaces de las páginas.
            ->withQueryString();

        // TEMPORAL: reemplazar por la vista Blade en la tarea de vistas
        return response()->json($usuarios);
    }

    // CREAR un usuario (CU04 pasos 4 a 7) con su rol (CU05).
    public function store(Request $request): RedirectResponse
    {
        // CU04 paso 5: validar los datos.
        $datos = $request->validate(
            $this->reglasDatos() + [
                'password' => ['required', 'string', 'regex:'.User::REGEX_CONTRASENA, 'confirmed'],
                'rol' => ['required', Rule::in(['administrador', 'odontologo'])],
            ],
            $this->mensajes()
        );

        DB::transaction(function () use ($datos, $request) {
            // CU04 paso 6: guardar. La contraseña inicial la eligió el administrador,
            // por eso el titular debe reemplazarla en su primer ingreso.
            $usuario = User::create(Arr::only($datos, self::CAMPOS_DATOS) + [
                'password' => Hash::make($datos['password']),
                'rol' => $datos['rol'],
                'estado' => 'activo',
                'debe_cambiar_contrasena' => true,
            ]);

            // CU04 paso 7: registrar en la bitácora.
            BitacoraService::registrar(BitacoraService::CREAR_USUARIO, $request->user()->id, 'users', $usuario->id);
        });

        return redirect()->back()->with('exito', 'Usuario registrado correctamente.');
    }

    // MOSTRAR UN usuario con el resumen de su acceso (CU04 consultar y CU06 paso 2).
    public function show($id)
    {
        $usuario = User::with('especialidad')->findOrFail($id);

        // El hash de la contraseña no sale en el JSON: está en $hidden del modelo User.
        // TEMPORAL: reemplazar por la vista Blade en la tarea de vistas
        return response()->json([
            'usuario' => $usuario,
            'acceso' => [
                'bloqueado' => $usuario->estaBloqueado(),
                'bloqueado_hasta' => $usuario->estaBloqueado()
                    ? $usuario->bloqueado_hasta->format('Y-m-d H:i:s')
                    : null,
                'minutos_restantes' => $usuario->minutosBloqueoRestantes(),
                'intentos_fallidos' => $usuario->intentos_fallidos,
                'debe_cambiar_contrasena' => $usuario->debe_cambiar_contrasena,
            ],
        ]);
    }

    // MODIFICAR los datos de un usuario (CU04). El id viene en el formulario.
    // No cambia contraseña (CU03/CU06), rol (CU05) ni estado (delete/activar).
    public function update(Request $request): RedirectResponse
    {
        // Primero se valida el id: se usa luego en ignore(), que no debe recibir datos sin validar.
        $request->validate($this->reglaId(), $this->mensajes());

        // Mismas reglas que al crear, pero la unicidad ignora al propio usuario.
        $datos = $request->validate($this->reglasDatos((int) $request->input('id')), $this->mensajes());

        $usuario = User::findOrFail($request->input('id'));

        DB::transaction(function () use ($usuario, $datos, $request) {
            $usuario->update(Arr::only($datos, self::CAMPOS_DATOS));

            BitacoraService::registrar(BitacoraService::MODIFICAR_USUARIO, $request->user()->id, 'users', $usuario->id);
        });

        return redirect()->back()->with('exito', 'Usuario actualizado correctamente.');
    }

    // ELIMINAR = INHABILITAR un usuario (CU04 paso 8). No borra el registro (RNF-06).
    public function delete(Request $request): RedirectResponse
    {
        $request->validate($this->reglaId(), $this->mensajes());

        $usuario = User::findOrFail($request->input('id'));

        if ($usuario->is($request->user())) {
            return $this->error('No puede inhabilitar su propia cuenta.');
        }

        if ($usuario->rol === 'administrador' && $usuario->estado === 'activo' && $this->administradoresActivos() <= 1) {
            return $this->error('No se puede inhabilitar al único administrador activo.');
        }

        if ($usuario->estado === 'inactivo') {
            return $this->error('El usuario ya está inhabilitado.');
        }

        DB::transaction(function () use ($usuario, $request) {
            $usuario->estado = 'inactivo';
            $usuario->save();

            BitacoraService::registrar(BitacoraService::DESACTIVAR_USUARIO, $request->user()->id, 'users', $usuario->id);

            // Pierde el acceso de inmediato: se borran sus sesiones abiertas.
            DB::table('sessions')->where('user_id', $usuario->id)->delete();
        });

        return redirect()->back()->with('exito', 'Usuario inhabilitado correctamente. Sus registros se conservan.');
    }

    // HABILITAR un usuario inhabilitado (CU04). El id viene en el formulario.
    public function activar(Request $request): RedirectResponse
    {
        $request->validate($this->reglaId(), $this->mensajes());

        $usuario = User::findOrFail($request->input('id'));

        if ($usuario->estado === 'activo') {
            return $this->error('El usuario ya está habilitado.');
        }

        DB::transaction(function () use ($usuario, $request) {
            $usuario->estado = 'activo';
            $usuario->save();

            BitacoraService::registrar(BitacoraService::ACTIVAR_USUARIO, $request->user()->id, 'users', $usuario->id);
        });

        return redirect()->back()->with('exito', 'Usuario habilitado correctamente.');
    }

    // CU05 pasos 3 a 7: ASIGNAR el rol de un usuario. El id viene en el formulario.
    public function asignarRol(Request $request): RedirectResponse
    {
        $request->validate($this->reglaId() + [
            'rol' => ['required', Rule::in(['administrador', 'odontologo'])],
        ], $this->mensajes());

        $usuario = User::findOrFail($request->input('id'));
        $rolNuevo = $request->input('rol');

        if ($usuario->estado === 'inactivo') {
            return $this->error('No se puede asignar un rol a un usuario inactivo.');
        }

        if ($usuario->rol === $rolNuevo) {
            return $this->error('El usuario ya tiene ese rol.');
        }

        // Si se le quita el rol de administrador, debe quedar al menos otro administrador activo.
        if ($usuario->rol === 'administrador' && $this->administradoresActivos() <= 1) {
            return $this->error('Debe quedar al menos un administrador activo.');
        }

        DB::transaction(function () use ($usuario, $rolNuevo, $request) {
            $usuario->rol = $rolNuevo;
            $usuario->save();

            BitacoraService::registrar(BitacoraService::CAMBIO_ROL, $request->user()->id, 'users', $usuario->id);
        });

        // CU05 paso 6: el nuevo rol se aplica desde la siguiente solicitud (middleware rol).
        // Si el administrador se quitó a sí mismo el rol, ya no puede volver a esta sección,
        // así que se lo envía a inicio en lugar de volver atrás.
        if ($usuario->is($request->user())) {
            return redirect()->route('inicio')->with('exito', 'Rol actualizado correctamente.');
        }

        return redirect()->back()->with('exito', 'Rol actualizado correctamente.');
    }

    // CU06 pasos 3 a 7: RESTABLECER el acceso de un usuario. El id viene en el formulario.
    public function restablecerAcceso(Request $request): RedirectResponse
    {
        $request->validate($this->reglaId(), $this->mensajes());

        $usuario = User::findOrFail($request->input('id'));

        if ($usuario->is($request->user())) {
            return $this->error('No puede restablecer su propia cuenta. Use la opción Cambiar contraseña.');
        }

        if ($usuario->estado === 'inactivo') {
            return $this->error('La cuenta está inhabilitada. Habilítela primero en Gestionar usuarios.');
        }

        // CU06 paso 4: generar la contraseña temporal (RN-01).
        $temporal = $this->generarContrasenaTemporal();

        DB::transaction(function () use ($usuario, $temporal, $request) {
            // CU06 paso 5: guardar la temporal cifrada, desbloquear y obligar a cambiarla.
            $usuario->password = Hash::make($temporal);
            $usuario->intentos_fallidos = 0;
            $usuario->bloqueado_hasta = null;
            $usuario->debe_cambiar_contrasena = true;
            $usuario->save();

            // CU06 paso 6: registrar en la bitácora (nunca la contraseña temporal).
            BitacoraService::registrar(BitacoraService::RESTABLECER_ACCESO, $request->user()->id, 'users', $usuario->id);

            // Se cierran las sesiones abiertas de ese usuario.
            DB::table('sessions')->where('user_id', $usuario->id)->delete();
        });

        // CU06 paso 7: mostrar la temporal una sola vez. Los datos de with() duran
        // una sola petición. Nunca se escribe en la bitácora ni en los logs.
        return redirect()->back()
            ->with('exito', 'Acceso restablecido. Entregue personalmente la contraseña temporal; se muestra una sola vez.')
            ->with('contrasena_temporal', $temporal)
            ->with('usuario_restablecido', $usuario->usuario);
    }

    /**
     * Genera una contraseña temporal de 12 caracteres que cumple RN-01.
     * Usa random_int (generador seguro) y evita caracteres confusos
     * como l, I, O, 0 y 1.
     */
    private function generarContrasenaTemporal(): string
    {
        $minusculas = 'abcdefghijkmnpqrstuvwxyz';
        $mayusculas = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $numeros = '23456789';
        $especiales = '!@#$%&*?';
        $todos = $minusculas.$mayusculas.$numeros.$especiales;

        do {
            // Al menos uno de cada tipo.
            $caracteres = [
                $this->caracterAlAzar($minusculas),
                $this->caracterAlAzar($mayusculas),
                $this->caracterAlAzar($numeros),
                $this->caracterAlAzar($especiales),
            ];

            // Se completa hasta 12 con caracteres al azar de cualquier tipo.
            while (count($caracteres) < 12) {
                $caracteres[] = $this->caracterAlAzar($todos);
            }

            // Se mezclan (Fisher-Yates con random_int) para que los primeros
            // cuatro no estén siempre en el mismo orden.
            for ($i = count($caracteres) - 1; $i > 0; $i--) {
                $j = random_int(0, $i);
                [$caracteres[$i], $caracteres[$j]] = [$caracteres[$j], $caracteres[$i]];
            }

            $temporal = implode('', $caracteres);
            // Verificación final contra la política RN-01.
        } while (! preg_match(User::REGEX_CONTRASENA, $temporal));

        return $temporal;
    }

    // Devuelve un carácter al azar del conjunto recibido.
    private function caracterAlAzar(string $conjunto): string
    {
        return $conjunto[random_int(0, strlen($conjunto) - 1)];
    }

    // Cantidad de administradores activos (para no dejar el sistema sin administrador).
    private function administradoresActivos(): int
    {
        return User::where('rol', 'administrador')->where('estado', 'activo')->count();
    }

    // Vuelve atrás con un error de negocio (clave "operacion").
    private function error(string $mensaje): RedirectResponse
    {
        return redirect()->back()->withErrors(['operacion' => $mensaje]);
    }

    // Regla del id que llega oculto en el formulario.
    private function reglaId(): array
    {
        return ['id' => ['required', 'integer', 'exists:users,id']];
    }

    /**
     * Reglas de los datos personales, comunes a crear y modificar.
     * $idIgnorar: al modificar, la unicidad de CI, usuario y correo ignora al propio usuario.
     */
    private function reglasDatos(?int $idIgnorar = null): array
    {
        return [
            'nombres' => ['required', 'string', 'max:50'],
            'apellidos' => ['required', 'string', 'max:50'],
            'ci' => ['required', 'string', 'max:15', Rule::unique('users', 'ci')->ignore($idIgnorar)],
            'matricula_profesional' => ['required', 'string', 'max:20'],
            'especialidad_id' => ['required', 'integer', 'exists:especialidades,id'],
            'correo' => ['required', 'string', 'email', 'max:100', Rule::unique('users', 'correo')->ignore($idIgnorar)],
            'telefono' => ['required', 'string', 'max:15'],
            'usuario' => ['required', 'string', 'between:4,30', 'regex:/^\S+$/', Rule::unique('users', 'usuario')->ignore($idIgnorar)],
        ];
    }

    // Mensajes en español de todas las reglas de este controlador.
    private function mensajes(): array
    {
        $mensajeUsuario = 'El usuario debe tener entre 4 y 30 caracteres y no contener espacios.';

        return [
            'id.required' => 'No se indicó el usuario.',
            'id.integer' => 'El usuario indicado no es válido.',
            'id.exists' => 'El usuario indicado no existe.',

            'nombres.required' => 'El campo Nombres es obligatorio.',
            'nombres.string' => 'El campo Nombres debe ser un texto.',
            'nombres.max' => 'Los nombres no pueden superar los 50 caracteres.',
            'apellidos.required' => 'El campo Apellidos es obligatorio.',
            'apellidos.string' => 'El campo Apellidos debe ser un texto.',
            'apellidos.max' => 'Los apellidos no pueden superar los 50 caracteres.',
            'ci.required' => 'El campo CI es obligatorio.',
            'ci.string' => 'El campo CI debe ser un texto.',
            'ci.max' => 'El CI no puede superar los 15 caracteres.',
            'ci.unique' => 'El CI ya está registrado.',
            'matricula_profesional.required' => 'El campo Matrícula profesional es obligatorio.',
            'matricula_profesional.string' => 'El campo Matrícula profesional debe ser un texto.',
            'matricula_profesional.max' => 'La matrícula profesional no puede superar los 20 caracteres.',
            'especialidad_id.required' => 'Debe seleccionar una especialidad.',
            'especialidad_id.integer' => 'La especialidad seleccionada no es válida.',
            'especialidad_id.exists' => 'La especialidad seleccionada no existe.',
            'correo.required' => 'El campo Correo es obligatorio.',
            'correo.string' => 'El campo Correo debe ser un texto.',
            'correo.email' => 'El correo no tiene un formato válido.',
            'correo.max' => 'El correo no puede superar los 100 caracteres.',
            'correo.unique' => 'El correo ya está registrado.',
            'telefono.required' => 'El campo Teléfono es obligatorio.',
            'telefono.string' => 'El campo Teléfono debe ser un texto.',
            'telefono.max' => 'El teléfono no puede superar los 15 caracteres.',
            'usuario.required' => 'El campo Usuario es obligatorio.',
            'usuario.string' => $mensajeUsuario,
            'usuario.between' => $mensajeUsuario,
            'usuario.regex' => $mensajeUsuario,
            'usuario.unique' => 'El nombre de usuario ya está registrado.',
            'password.required' => 'El campo Contraseña es obligatorio.',
            'password.string' => User::MENSAJE_CONTRASENA,
            'password.regex' => User::MENSAJE_CONTRASENA,
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'rol.required' => 'Debe seleccionar un rol.',
            'rol.in' => 'El rol debe ser administrador u odontólogo.',
        ];
    }
}
