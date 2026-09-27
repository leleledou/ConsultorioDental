<?php

namespace App\Http\Controllers;

use App\Models\HistoriaClinica;
use App\Models\Paciente;
use App\Services\BitacoraService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CU08 Gestionar pacientes y CU09 Buscar paciente.
 * Para los dos roles (el administrador también es odontólogo).
 * Nunca se borran pacientes de la base: "eliminar" significa inhabilitar.
 */
class PacienteController extends Controller
{
    // MOSTRAR la lista de pacientes, con buscador por CI, nombres o apellidos (CU09).
    public function index(Request $request): View
    {
        // CU09 paso 2: validar el texto de búsqueda.
        $request->validate(
            ['buscar' => ['nullable', 'string', 'max:100']],
            ['buscar.max' => 'El texto de búsqueda no puede superar los 100 caracteres.']
        );

        $buscar = $request->input('buscar');

        // CU09 paso 3: buscar. Los orWhere van agrupados en un solo bloque entre paréntesis.
        $pacientes = Paciente::query()
            ->when($buscar, function ($consulta, $buscar) {
                $consulta->where(function ($grupo) use ($buscar) {
                    $grupo->where('ci', 'like', "%{$buscar}%")
                        ->orWhere('nombres', 'like', "%{$buscar}%")
                        ->orWhere('apellidos', 'like', "%{$buscar}%");
                });
            })
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->paginate(10)
            // Conserva ?buscar=... en los enlaces de las páginas.
            ->withQueryString();

        // CU09 paso 4: mostrar los resultados (la vista avisa si no hay coincidencias).
        return view('pacientes.index', compact('pacientes', 'buscar'));
    }

    // CREAR un paciente junto con su historia clínica (CU08 pasos 3 a 6).
    public function store(Request $request): RedirectResponse
    {
        // CU08 paso 4: validar los datos.
        $datos = $request->validate($this->reglasDatos(), $this->mensajes());

        DB::transaction(function () use ($datos, $request) {
            // CU08 paso 5: guardar el paciente, activo y con la fecha de hoy.
            $paciente = Paciente::create($datos + [
                'estado' => 'activo',
                'fecha_registro' => today(),
            ]);

            // En la misma transacción se abre su historia clínica.
            HistoriaClinica::create([
                'paciente_id' => $paciente->id,
                'fecha_apertura' => today(),
            ]);

            // CU08 paso 6: registrar en la bitácora.
            BitacoraService::registrar(BitacoraService::CREAR_PACIENTE, $request->user()->id, 'pacientes', $paciente->id);
        });

        return redirect()->back()->with('exito', 'Paciente registrado correctamente. Se abrió su historia clínica.');
    }

    // MOSTRAR la ficha de UN paciente, con la edad calculada (CU08 consultar).
    public function show($id): View
    {
        $paciente = Paciente::with('historiaClinica')->findOrFail($id);

        return view('pacientes.show', compact('paciente'));
    }

    // MODIFICAR los datos de un paciente (CU08). El id viene en el formulario.
    // No cambia el estado (delete/activar) ni la fecha de registro.
    public function update(Request $request): RedirectResponse
    {
        // Primero se valida el id: se usa luego en ignore(), que no debe recibir datos sin validar.
        $request->validate($this->reglaId(), $this->mensajes());

        $paciente = Paciente::findOrFail($request->input('id'));

        // Mismas reglas que al crear; la unicidad del CI ignora al propio paciente y la
        // fecha de nacimiento no puede pasar su fecha de registro (restricción de la base).
        $datos = $request->validate(
            $this->reglasDatos($paciente->id, $paciente->fecha_registro->toDateString()),
            $this->mensajes()
        );

        DB::transaction(function () use ($paciente, $datos, $request) {
            $paciente->update($datos);

            BitacoraService::registrar(BitacoraService::MODIFICAR_PACIENTE, $request->user()->id, 'pacientes', $paciente->id);
        });

        return redirect()->back()->with('exito', 'Paciente actualizado correctamente.');
    }

    // ELIMINAR = INHABILITAR un paciente (CU08). No borra el registro ni su historia.
    public function delete(Request $request): RedirectResponse
    {
        $request->validate($this->reglaId(), $this->mensajes());

        $paciente = Paciente::findOrFail($request->input('id'));

        if ($paciente->estado === 'inactivo') {
            return $this->error('El paciente ya está inhabilitado.');
        }

        DB::transaction(function () use ($paciente, $request) {
            $paciente->estado = 'inactivo';
            $paciente->save();

            BitacoraService::registrar(BitacoraService::INHABILITAR_PACIENTE, $request->user()->id, 'pacientes', $paciente->id);
        });

        return redirect()->back()->with('exito', 'Paciente inhabilitado correctamente. Su historia clínica se conserva.');
    }

    // HABILITAR un paciente inhabilitado (CU08). El id viene en el formulario.
    public function activar(Request $request): RedirectResponse
    {
        $request->validate($this->reglaId(), $this->mensajes());

        $paciente = Paciente::findOrFail($request->input('id'));

        if ($paciente->estado === 'activo') {
            return $this->error('El paciente ya está habilitado.');
        }

        DB::transaction(function () use ($paciente, $request) {
            $paciente->estado = 'activo';
            $paciente->save();

            BitacoraService::registrar(BitacoraService::ACTIVAR_PACIENTE, $request->user()->id, 'pacientes', $paciente->id);
        });

        return redirect()->back()->with('exito', 'Paciente habilitado correctamente.');
    }

    // Vuelve atrás con un error de negocio (clave "operacion").
    private function error(string $mensaje): RedirectResponse
    {
        return redirect()->back()->withErrors(['operacion' => $mensaje]);
    }

    // Regla del id que llega oculto en el formulario.
    private function reglaId(): array
    {
        return ['id' => ['required', 'integer', 'exists:pacientes,id']];
    }

    /**
     * Reglas de los datos del paciente, comunes a crear y modificar.
     * $idIgnorar: al modificar, la unicidad del CI ignora al propio paciente.
     * $fechaLimite: la fecha de nacimiento no puede ser posterior a esta fecha
     * (hoy al crear; la fecha de registro al modificar).
     */
    private function reglasDatos(?int $idIgnorar = null, string $fechaLimite = 'today'): array
    {
        return [
            'ci' => ['required', 'string', 'max:15', Rule::unique('pacientes', 'ci')->ignore($idIgnorar)],
            'nombres' => ['required', 'string', 'max:50'],
            'apellidos' => ['required', 'string', 'max:50'],
            'fecha_nacimiento' => ['required', 'date', 'after_or_equal:1900-01-01', 'before_or_equal:'.$fechaLimite],
            'sexo' => ['required', Rule::in(['F', 'M'])],
            'telefono' => ['nullable', 'string', 'max:15'],
            'correo' => ['nullable', 'string', 'email', 'max:100'],
            'direccion' => ['nullable', 'string', 'max:150'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ];
    }

    // Mensajes en español de todas las reglas de este controlador.
    private function mensajes(): array
    {
        return [
            'id.required' => 'No se indicó el paciente.',
            'id.integer' => 'El paciente indicado no es válido.',
            'id.exists' => 'El paciente indicado no existe.',

            'ci.required' => 'El campo CI es obligatorio.',
            'ci.string' => 'El campo CI debe ser un texto.',
            'ci.max' => 'El CI no puede superar los 15 caracteres.',
            'ci.unique' => 'Ya existe un paciente registrado con ese CI.',
            'nombres.required' => 'El campo Nombres es obligatorio.',
            'nombres.string' => 'El campo Nombres debe ser un texto.',
            'nombres.max' => 'Los nombres no pueden superar los 50 caracteres.',
            'apellidos.required' => 'El campo Apellidos es obligatorio.',
            'apellidos.string' => 'El campo Apellidos debe ser un texto.',
            'apellidos.max' => 'Los apellidos no pueden superar los 50 caracteres.',
            'fecha_nacimiento.required' => 'El campo Fecha de nacimiento es obligatorio.',
            'fecha_nacimiento.date' => 'La fecha de nacimiento no es válida.',
            'fecha_nacimiento.after_or_equal' => 'La fecha de nacimiento no puede ser anterior a 1900.',
            'fecha_nacimiento.before_or_equal' => 'La fecha de nacimiento no puede ser futura ni posterior a la fecha de registro del paciente.',
            'sexo.required' => 'Debe seleccionar el sexo.',
            'sexo.in' => 'El sexo debe ser F o M.',
            'telefono.string' => 'El campo Teléfono debe ser un texto.',
            'telefono.max' => 'El teléfono no puede superar los 15 caracteres.',
            'correo.string' => 'El campo Correo debe ser un texto.',
            'correo.email' => 'El correo no tiene un formato válido.',
            'correo.max' => 'El correo no puede superar los 100 caracteres.',
            'direccion.string' => 'El campo Dirección debe ser un texto.',
            'direccion.max' => 'La dirección no puede superar los 150 caracteres.',
            'observaciones.string' => 'El campo Observaciones debe ser un texto.',
            'observaciones.max' => 'Las observaciones no pueden superar los 1000 caracteres.',
        ];
    }
}
