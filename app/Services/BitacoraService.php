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

    // Acciones de CU14 Gestionar especialidades.
    public const CREAR_ESPECIALIDAD = 'CREAR_ESPECIALIDAD';
    public const MODIFICAR_ESPECIALIDAD = 'MODIFICAR_ESPECIALIDAD';
    public const ELIMINAR_ESPECIALIDAD = 'ELIMINAR_ESPECIALIDAD';

    // Acciones de CU08 Gestionar pacientes.
    public const CREAR_PACIENTE = 'CREAR_PACIENTE';
    public const MODIFICAR_PACIENTE = 'MODIFICAR_PACIENTE';
    public const INHABILITAR_PACIENTE = 'INHABILITAR_PACIENTE';
    public const ACTIVAR_PACIENTE = 'ACTIVAR_PACIENTE';

    // Acciones de CU15 Gestionar catálogo de tratamientos.
    public const CREAR_TRATAMIENTO = 'CREAR_TRATAMIENTO';
    public const MODIFICAR_TRATAMIENTO = 'MODIFICAR_TRATAMIENTO';
    public const BAJA_TRATAMIENTO = 'BAJA_TRATAMIENTO';
    public const HABILITAR_TRATAMIENTO = 'HABILITAR_TRATAMIENTO';

    // Acción de CU10 Programar cita.
    public const PROGRAMAR_CITA = 'PROGRAMAR_CITA';

    // Acciones de CU11 Reprogramar o cancelar cita.
    public const REPROGRAMAR_CITA = 'REPROGRAMAR_CITA';
    public const CANCELAR_CITA = 'CANCELAR_CITA';

    // Acción de CU12 Actualizar estado de cita.
    public const CAMBIO_ESTADO_CITA = 'CAMBIO_ESTADO_CITA';

    // Acción del catálogo de tarifas.
    public const CREAR_TARIFA = 'CREAR_TARIFA';

    // Acciones del catálogo de diagnósticos.
    public const CREAR_DIAGNOSTICO = 'CREAR_DIAGNOSTICO';
    public const MODIFICAR_DIAGNOSTICO = 'MODIFICAR_DIAGNOSTICO';
    public const BAJA_DIAGNOSTICO = 'BAJA_DIAGNOSTICO';
    public const HABILITAR_DIAGNOSTICO = 'HABILITAR_DIAGNOSTICO';

    // Acciones del catálogo de tipos de estudio.
    public const CREAR_TIPO_ESTUDIO = 'CREAR_TIPO_ESTUDIO';
    public const MODIFICAR_TIPO_ESTUDIO = 'MODIFICAR_TIPO_ESTUDIO';
    public const BAJA_TIPO_ESTUDIO = 'BAJA_TIPO_ESTUDIO';
    public const HABILITAR_TIPO_ESTUDIO = 'HABILITAR_TIPO_ESTUDIO';

    // Acciones de los estudios requeridos por tratamiento.
    public const ASIGNAR_ESTUDIO_REQUERIDO = 'ASIGNAR_ESTUDIO_REQUERIDO';
    public const MODIFICAR_ESTUDIO_REQUERIDO = 'MODIFICAR_ESTUDIO_REQUERIDO';
    public const QUITAR_ESTUDIO_REQUERIDO = 'QUITAR_ESTUDIO_REQUERIDO';

    // Acciones del catálogo de materiales.
    public const CREAR_MATERIAL = 'CREAR_MATERIAL';
    public const MODIFICAR_MATERIAL = 'MODIFICAR_MATERIAL';
    public const BAJA_MATERIAL = 'BAJA_MATERIAL';
    public const HABILITAR_MATERIAL = 'HABILITAR_MATERIAL';

    // Acciones del catálogo de antecedentes médicos.
    public const CREAR_ANTECEDENTE = 'CREAR_ANTECEDENTE';
    public const MODIFICAR_ANTECEDENTE = 'MODIFICAR_ANTECEDENTE';
    public const ELIMINAR_ANTECEDENTE = 'ELIMINAR_ANTECEDENTE';

    // Acciones del catálogo de estados de pieza.
    public const CREAR_ESTADO_PIEZA = 'CREAR_ESTADO_PIEZA';
    public const MODIFICAR_ESTADO_PIEZA = 'MODIFICAR_ESTADO_PIEZA';
    public const BAJA_ESTADO_PIEZA = 'BAJA_ESTADO_PIEZA';
    public const HABILITAR_ESTADO_PIEZA = 'HABILITAR_ESTADO_PIEZA';

    // Acciones del catálogo de métodos de pago.
    public const CREAR_METODO_PAGO = 'CREAR_METODO_PAGO';
    public const MODIFICAR_METODO_PAGO = 'MODIFICAR_METODO_PAGO';
    public const BAJA_METODO_PAGO = 'BAJA_METODO_PAGO';
    public const HABILITAR_METODO_PAGO = 'HABILITAR_METODO_PAGO';

    // Acciones de la historia clínica y sus antecedentes.
    public const MODIFICAR_HISTORIA = 'MODIFICAR_HISTORIA';
    public const ASIGNAR_ANTECEDENTE = 'ASIGNAR_ANTECEDENTE';
    public const MODIFICAR_ANTECEDENTE_HISTORIA = 'MODIFICAR_ANTECEDENTE_HISTORIA';
    public const QUITAR_ANTECEDENTE = 'QUITAR_ANTECEDENTE';

    // Acciones del plan de tratamiento.
    public const CREAR_PLAN = 'CREAR_PLAN';
    public const CANCELAR_PLAN = 'CANCELAR_PLAN';
    public const REGISTRAR_TRATAMIENTO_APLICADO = 'REGISTRAR_TRATAMIENTO_APLICADO';
    public const CAMBIO_ESTADO_TRATAMIENTO = 'CAMBIO_ESTADO_TRATAMIENTO';

    // Acciones de los abonos.
    public const REGISTRAR_ABONO = 'REGISTRAR_ABONO';
    public const ANULAR_ABONO = 'ANULAR_ABONO';

    /**
     * Devuelve la lista de los valores de todas las acciones (todas las constantes).
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
