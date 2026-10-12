<?php

namespace Database\Seeders;

use App\Models\Cita;
use App\Models\EstadoCita;
use App\Models\Paciente;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CitaSeeder extends Seeder
{
    /**
     * Crea 11 citas de la semana actual (lunes a sábado) para que la agenda (CU13)
     * tenga datos. Ninguna se superpone con otra (un solo sillón, RN-07).
     *
     * El estado depende de la fecha en que se ejecute el seeder: las citas de días
     * pasados quedan atendidas o no asistidas; las de hoy en adelante, programadas.
     * Una cita está cancelada siempre, para ver ese color en la agenda.
     * hora_fin no se escribe: la calcula la base de datos.
     */
    public function run(): void
    {
        $lunes = today()->startOfWeek(Carbon::MONDAY);

        // [día (0 = lunes), hora, minutos, CI del paciente, usuario del odontólogo, motivo, estado si ya pasó]
        $citas = [
            [0, '08:30', 40, '6123456', 'rcalizaya', 'Profilaxis y limpieza', EstadoCita::ATENDIDA],
            [0, '10:00', 90, '5987321', 'gcallejas', 'Endodoncia unirradicular (1.ª sesión)', EstadoCita::ATENDIDA],
            [1, '09:00', 30, '13456789', 'rcalizaya', 'Control odontopediátrico', EstadoCita::NO_ASISTIDA],
            [1, '15:00', 60, '7894561', 'gcallejas', 'Restauración con resina', EstadoCita::ATENDIDA],
            [2, '08:00', 45, '4561237', 'rcalizaya', 'Extracción simple', EstadoCita::CANCELADA],
            [2, '11:00', 60, '8321654', 'rcalizaya', 'Evaluación para ortodoncia', EstadoCita::ATENDIDA],
            [3, '09:30', 120, '4561237', 'gcallejas', 'Implante dental (1.ª fase)', EstadoCita::ATENDIDA],
            [3, '16:00', 40, '3219876', 'rcalizaya', 'Profilaxis y limpieza', EstadoCita::ATENDIDA],
            [4, '10:00', 90, '5987321', 'gcallejas', 'Endodoncia unirradicular (2.ª sesión)', EstadoCita::ATENDIDA],
            [4, '14:30', 60, '7894561', 'rcalizaya', 'Control de restauración', EstadoCita::NO_ASISTIDA],
            [5, '09:00', 30, '13456789', 'rcalizaya', 'Control odontopediátrico', EstadoCita::ATENDIDA],
        ];

        foreach ($citas as [$dia, $hora, $minutos, $ci, $usuario, $motivo, $estadoPasado]) {
            $fecha = $lunes->copy()->addDays($dia);

            $estado = match (true) {
                $estadoPasado === EstadoCita::CANCELADA => EstadoCita::CANCELADA,
                $fecha->lt(today()) => $estadoPasado,
                default => EstadoCita::PROGRAMADA,
            };

            Cita::create([
                // Paciente y odontólogo se buscan por CI y usuario, nunca con un id escrito a mano.
                'paciente_id' => Paciente::where('ci', $ci)->firstOrFail()->id,
                'odontologo_id' => User::where('usuario', $usuario)->firstOrFail()->id,
                'tratamiento_aplicado_id' => null,
                'fecha' => $fecha->toDateString(),
                'hora_inicio' => $hora,
                'duracion_minutos' => $minutos,
                'motivo' => $motivo,
                'estado_cita_id' => EstadoCita::idDe($estado),
                'observaciones' => $estado === EstadoCita::CANCELADA ? 'Cancelada: el paciente avisó que no podía asistir.' : null,
                'fecha_registro' => $lunes->copy()->subDays(7)->toDateString(),
            ]);
        }
    }
}
