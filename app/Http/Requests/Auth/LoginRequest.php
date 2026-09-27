<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Services\BitacoraService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * CU01 Iniciar sesión (adaptado del LoginRequest de Breeze).
 *
 * Se entra con el nombre de usuario, no con el email. El RateLimiter de Breeze
 * se quitó: el bloqueo de RN-03 (3 intentos, 15 minutos, guardado en la base)
 * lo reemplaza.
 *
 * Orden obligatorio de CU01: formato (paso 4) -> cuenta (paso 5) ->
 * bloqueo (paso 6) -> contraseña (paso 7) -> éxito (pasos 8 a 10).
 */
class LoginRequest extends FormRequest
{
    // RN-04: mismo mensaje para usuario inexistente y cuenta inactiva,
    // para no revelar qué cuentas existen.
    private const MENSAJE_GENERICO = 'Usuario o contraseña incorrectos. Si el problema persiste, comuníquese con el administrador.';

    // Si falla la validación de formato, se vuelve siempre al formulario de login.
    protected $redirectRoute = 'login';

    /**
     * Cualquier visitante puede intentar iniciar sesión.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * CU01 paso 4: reglas de formato [A1].
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'usuario' => ['required', 'string', 'between:4,30', 'regex:/^\S+$/'],
            // Sin reglas de formato: cualquier contraseña incorrecta de un usuario
            // existente debe llegar al paso 7 y contar como intento fallido (RN-03).
            // La política RN-01 se exige al crear la contraseña (CU04 y CU03), no aquí.
            'password' => ['required', 'string'],
        ];
    }

    /**
     * CU01 paso 4: mensajes en español de las reglas de formato.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $mensajeUsuario = 'El usuario debe tener entre 4 y 30 caracteres y no contener espacios.';

        return [
            'usuario.required' => 'El campo Usuario es obligatorio.',
            'usuario.string' => $mensajeUsuario,
            'usuario.between' => $mensajeUsuario,
            'usuario.regex' => $mensajeUsuario,
            'password.required' => 'El campo Contraseña es obligatorio.',
            'password.string' => 'La contraseña no es válida.',
        ];
    }

    /**
     * CU01 paso 4, flujo [A1]: el formato no es válido.
     * Un Form Request valida antes de llegar al controlador, por eso el intento
     * se registra aquí. No se busca la cuenta ni se cambian contadores.
     * Luego Laravel vuelve al formulario conservando el usuario escrito;
     * la contraseña nunca se devuelve (está en la lista dontFlash de Laravel).
     */
    protected function failedValidation(Validator $validator)
    {
        BitacoraService::registrar(
            BitacoraService::INTENTO_LOGIN_DATOS_INVALIDOS,
            usuarioDigitado: $this->usuarioDigitado()
        );

        parent::failedValidation($validator);
    }

    /**
     * CU01 pasos 5 a 10: buscar la cuenta, verificar el bloqueo, comparar la
     * contraseña e iniciar la sesión. Se llama desde AuthenticatedSessionController.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        // Lo que la persona escribió como usuario; se guarda en la bitácora en cada intento.
        $usuarioDigitado = $this->usuarioDigitado();

        // CU01 paso 5: buscar la cuenta [A2].
        $usuario = User::where('usuario', $this->input('usuario'))->first();

        if (! $usuario) {
            // No existe: usuario_id queda null y se guarda lo escrito.
            BitacoraService::registrar(
                BitacoraService::LOGIN_FALLIDO_USUARIO_INEXISTENTE,
                usuarioDigitado: $usuarioDigitado
            );

            $this->rechazar(self::MENSAJE_GENERICO);
        }

        if ($usuario->estado === 'inactivo') {
            BitacoraService::registrar(
                BitacoraService::LOGIN_FALLIDO_CUENTA_INACTIVA,
                $usuario->id,
                usuarioDigitado: $usuarioDigitado
            );

            $this->rechazar(self::MENSAJE_GENERICO);
        }

        // CU01 paso 6: verificar el bloqueo temporal [A4].
        if ($usuario->estaBloqueado()) {
            // Sigue bloqueada: no se verifica la contraseña ni se cambia el contador.
            $minutos = $usuario->minutosBloqueoRestantes();

            BitacoraService::registrar(
                BitacoraService::LOGIN_RECHAZADO_CUENTA_BLOQUEADA,
                $usuario->id,
                usuarioDigitado: $usuarioDigitado
            );

            $this->rechazar("Su cuenta está bloqueada temporalmente. Intente nuevamente en {$minutos} minutos o comuníquese con el administrador.");
        }

        if ($usuario->bloqueado_hasta !== null) {
            // El bloqueo ya terminó: se levanta y se continúa con el paso 7.
            $usuario->bloqueado_hasta = null;
            $usuario->intentos_fallidos = 0;
            $usuario->save();
        }

        // CU01 paso 7: comparar la contraseña contra el hash [A3].
        if (! Hash::check($this->input('password'), $usuario->password)) {
            $maximo = (int) config('dentalrox.intentos_maximos');
            $minutosBloqueo = (int) config('dentalrox.minutos_bloqueo');

            // El contador y la bitácora se guardan juntos: ocurren los dos o ninguno.
            $quedoBloqueada = DB::transaction(function () use ($usuario, $usuarioDigitado, $maximo, $minutosBloqueo) {
                $usuario->intentos_fallidos = $usuario->intentos_fallidos + 1;
                $usuario->save();

                BitacoraService::registrar(
                    BitacoraService::LOGIN_FALLIDO_CONTRASENA,
                    $usuario->id,
                    usuarioDigitado: $usuarioDigitado
                );

                if ($usuario->intentos_fallidos < $maximo) {
                    return false;
                }

                // RN-03: se alcanzó el máximo de intentos; se bloquea la cuenta.
                $usuario->bloqueado_hasta = now()->addMinutes($minutosBloqueo);
                $usuario->save();

                BitacoraService::registrar(
                    BitacoraService::CUENTA_BLOQUEADA,
                    $usuario->id,
                    usuarioDigitado: $usuarioDigitado
                );

                return true;
            });

            if ($quedoBloqueada) {
                $this->rechazar("Su cuenta fue bloqueada temporalmente por superar el número de intentos permitidos. Intente nuevamente en {$minutosBloqueo} minutos o comuníquese con el administrador.");
            }

            $this->rechazar("Usuario o contraseña incorrectos. Tras {$maximo} intentos fallidos la cuenta se bloqueará temporalmente.");
        }

        // CU01 pasos 8 y 10: reiniciar los contadores y registrar el inicio de sesión
        // exitoso en una transacción, ANTES de Auth::login: así nadie queda con la
        // sesión iniciada sin su registro en la bitácora.
        DB::transaction(function () use ($usuario, $usuarioDigitado) {
            $usuario->intentos_fallidos = 0;
            $usuario->bloqueado_hasta = null;
            $usuario->save();

            BitacoraService::registrar(
                BitacoraService::INICIO_SESION_EXITOSO,
                $usuario->id,
                usuarioDigitado: $usuarioDigitado
            );
        });

        // CU01 paso 9: iniciar la sesión, sin "recordarme" (una sesión recordada
        // saltaría el cierre por inactividad de RNF-03).
        // La regeneración de la sesión y la redirección se hacen en el controlador.
        Auth::login($usuario);
    }

    /**
     * Detiene el login con un único mensaje en la clave "usuario" y vuelve al
     * formulario conservando solo el usuario escrito.
     *
     * @throws ValidationException
     */
    private function rechazar(string $mensaje): never
    {
        throw ValidationException::withMessages(['usuario' => $mensaje])
            ->redirectTo(route('login'));
    }

    /**
     * Devuelve el usuario escrito en el formulario, para la bitácora.
     * Se recorta a 30 caracteres porque la columna usuario_digitado es string(30):
     * un texto más largo (que no pasa la validación) haría fallar el INSERT.
     * Devuelve null si vino vacío.
     */
    private function usuarioDigitado(): ?string
    {
        $valor = $this->input('usuario');

        if (! is_string($valor) || trim($valor) === '') {
            return null;
        }

        return mb_substr($valor, 0, 30);
    }
}
