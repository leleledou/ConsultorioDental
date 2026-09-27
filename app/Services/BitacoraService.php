<?php

namespace App\Services;

use App\Models\Bitacora;

/**
 * Servicio central para registrar acciones en la bitácora.
 *
 * Todos los casos de uso del Ciclo 1 lo usan, para no repetir el mismo
 * código de registro en cada controlador.
 */
class BitacoraService
{
    // Acciones de CU01 Iniciar sesión.
    public const INICIO_SESION_EXITOSO = 'INICIO_SESION_EXITOSO';
    public const INTENTO_LOGIN_DATOS_INVALIDOS = 'INTENTO_LOGIN_DATOS_INVALIDOS';
    public const LOGIN_FALLIDO_USUARIO_INEXISTENTE = 'LOGIN_FALLIDO_USUARIO_INEXISTENTE';
    public const LOGIN_FALLIDO_CUENTA_INACTIVA = 'LOGIN_FALLIDO_CUENTA_INACTIVA';
    public const LOGIN_FALLIDO_CONTRASENA = 'LOGIN_FALLIDO_CONTRASENA';
    public const CUENTA_BLOQUEADA = 'CUENTA_BLOQUEADA';
    public const LOGIN_RECHAZADO_CUENTA_BLOQUEADA = 'LOGIN_RECHAZADO_CUENTA_BLOQUEADA';

    // Acciones de CU02 Cerrar sesión.
    public const CIERRE_SESION = 'CIERRE_SESION';
    public const CIERRE_SESION_INACTIVIDAD = 'CIERRE_SESION_INACTIVIDAD';

    // Acción de CU03 Cambiar contraseña.
    public const CAMBIO_CONTRASENA = 'CAMBIO_CONTRASENA';

    // Acciones de CU04 Gestionar usuarios.
    public const CREAR_USUARIO = 'CREAR_USUARIO';
    public const MODIFICAR_USUARIO = 'MODIFICAR_USUARIO';
    public const DESACTIVAR_USUARIO = 'DESACTIVAR_USUARIO';
    public const ACTIVAR_USUARIO = 'ACTIVAR_USUARIO';

    // Acción de CU05 Asignar rol.
    public const CAMBIO_ROL = 'CAMBIO_ROL';

    // Acción de CU06 Restablecer acceso.
    public const RESTABLECER_ACCESO = 'RESTABLECER_ACCESO';

    /**
     * Devuelve la lista de los valores de todas las acciones (las 16 constantes).
     * Se usa para validar el filtro "accion" de CU07 Consultar bitácora.
     * Se leen las constantes de la clase, así una acción nueva se incluye sola.
     *
     * @return list<string>
     */
    public static function acciones(): array
    {
        return array_values((new \ReflectionClass(self::class))->getConstants());
    }

    /**
     * Registra una acción en la bitácora y devuelve el registro creado.
     *
     * @param  string  $accion  Una de las constantes de esta clase.
     * @param  int|null  $usuarioId  Usuario que realiza la acción. Si es null y hay
     *                               sesión iniciada, se usa el usuario de la sesión.
     * @param  string|null  $tablaAfectada  Tabla afectada (null si la acción no afecta una tabla).
     * @param  int|null  $registroId  Id del registro afectado en esa tabla.
     * @param  string|null  $usuarioDigitado  Usuario escrito en el formulario de login
     *                                        (solo en los intentos de login).
     *
     * Ejemplo en el login (intento con un usuario que no existe):
     *   BitacoraService::registrar(
     *       BitacoraService::LOGIN_FALLIDO_USUARIO_INEXISTENTE,
     *       usuarioDigitado: $request->input('usuario')
     *   );
     *
     * Ejemplo al crear un usuario (el administrador tiene la sesión iniciada):
     *   $usuario = User::create([...]);
     *   BitacoraService::registrar(BitacoraService::CREAR_USUARIO, null, 'users', $usuario->id);
     */
    public static function registrar(
        string $accion,
        ?int $usuarioId = null,
        ?string $tablaAfectada = null,
        ?int $registroId = null,
        ?string $usuarioDigitado = null
    ): Bitacora {
        return Bitacora::create([
            // Si no se indica el usuario, se toma el de la sesión; sin sesión queda null.
            'usuario_id' => $usuarioId ?? auth()->id(),
            'usuario_digitado' => $usuarioDigitado,
            'accion' => $accion,
            'tabla_afectada' => $tablaAfectada,
            'registro_id' => $registroId,
            'fecha_hora' => now(),
            'ip' => request()->ip(),
        ]);
    }
}
