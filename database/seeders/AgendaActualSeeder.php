<?php

namespace Database\Seeders;

use App\Models\Cita;
use App\Models\EstadoCita;
use App\Models\Paciente;
use App\Models\Tratamiento;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use RuntimeException;

class AgendaActualSeeder extends Seeder
{
    /**
     * Crea citas para la semana actual y las dos siguientes (lunes a sábado), para
     * que la agenda (CU13) y las citas del día siempre tengan datos, sin importar
     * cuándo se ejecute el seeder. Va al final de DatabaseSeeder.
     *
     * El estado depende de la fecha en que se ejecute: las citas de días pasados
     * quedan atendidas o no asistidas; las de hoy en adelante, programadas.
     * Una cita por semana está cancelada siempre, para ver ese color en la agenda.
     *
     * La duración y el motivo salen del tratamiento (RN-08). tratamiento_aplicado_id
     * queda en null: son citas de agenda, sin sesiones registradas.
     * hora_fin no se escribe: la calcula la base de datos.
     */
    public function run(): void
    {
        $lunes = today()->startOfWeek(Carbon::MONDAY);

        // [semana (0 = actual), día (0 = lunes), hora, CI del paciente, usuario del odontólogo, tratamiento, estado si ya pasó]
        $citas = [
            [0, 0, '08:30', '7845213', 'gcallejas', 'Endodoncia unirradicular', EstadoCita::ATENDIDA],
            [0, 0, '10:00', '9874561', 'rcalizaya', 'Ortodoncia con brackets', EstadoCita::ATENDIDA],
            [0, 0, '15:00', '7712340', 'gcallejas', 'Limpieza dental', EstadoCita::NO_ASISTIDA],
            [0, 1, '09:00', '10254789', 'rcalizaya', 'Aplicación de flúor', EstadoCita::ATENDIDA],
            [0, 1, '11:00', '4498123', 'gcallejas', 'Prótesis parcial removible', EstadoCita::ATENDIDA],
            [0, 1, '16:00', '8123098', 'gcallejas', 'Resina compuesta', EstadoCita::CANCELADA],
            [0, 2, '08:00', '9922341', 'rcalizaya', 'Ortodoncia con brackets', EstadoCita::ATENDIDA],
            [0, 2, '10:00', '4123698', 'gcallejas', 'Implante dental unitario', EstadoCita::ATENDIDA],
            [0, 2, '14:30', '6721098', 'gcallejas', 'Destartraje periodontal', EstadoCita::NO_ASISTIDA],
            [0, 3, '09:00', '5987412', 'gcallejas', 'Carilla de porcelana', EstadoCita::ATENDIDA],
            [0, 3, '11:00', '9013452', 'rcalizaya', 'Sellante de fosas y fisuras', EstadoCita::ATENDIDA],
            [0, 3, '15:30', '6598741', 'gcallejas', 'Carilla de porcelana', EstadoCita::ATENDIDA],
            [0, 3, '17:00', '10112233', 'rcalizaya', 'Aplicación de flúor', EstadoCita::NO_ASISTIDA],
            [0, 4, '08:30', '6512478', 'gcallejas', 'Extracción de tercer molar', EstadoCita::ATENDIDA],
            [0, 4, '10:30', '5423891', 'rcalizaya', 'Limpieza dental', EstadoCita::ATENDIDA],
            [0, 4, '16:00', '7009812', 'gcallejas', 'Blanqueamiento dental', EstadoCita::ATENDIDA],
            [0, 5, '09:00', '10334567', 'rcalizaya', 'Aplicación de flúor', EstadoCita::ATENDIDA],
            [0, 5, '10:00', '8845201', 'gcallejas', 'Resina compuesta', EstadoCita::NO_ASISTIDA],

            [1, 0, '08:30', '7156430', 'gcallejas', 'Limpieza dental', EstadoCita::ATENDIDA],
            [1, 0, '10:00', '4876520', 'rcalizaya', 'Extracción simple', EstadoCita::ATENDIDA],
            [1, 0, '15:00', '7845213', 'gcallejas', 'Endodoncia unirradicular', EstadoCita::ATENDIDA],
            [1, 1, '09:00', '6634512', 'gcallejas', 'Endodoncia multirradicular', EstadoCita::ATENDIDA],
            [1, 1, '11:00', '8123457', 'rcalizaya', 'Limpieza dental', EstadoCita::NO_ASISTIDA],
            [1, 1, '16:00', '5310987', 'gcallejas', 'Corona de porcelana', EstadoCita::ATENDIDA],
            [1, 2, '08:00', '4498123', 'gcallejas', 'Prótesis parcial removible', EstadoCita::ATENDIDA],
            [1, 2, '10:00', '9013452', 'rcalizaya', 'Limpieza dental', EstadoCita::ATENDIDA],
            [1, 2, '15:00', '8123098', 'gcallejas', 'Resina compuesta', EstadoCita::ATENDIDA],
            [1, 3, '09:30', '6598741', 'gcallejas', 'Carilla de porcelana', EstadoCita::ATENDIDA],
            [1, 3, '11:00', '10112233', 'rcalizaya', 'Sellante de fosas y fisuras', EstadoCita::ATENDIDA],
            [1, 3, '17:00', '7712340', 'gcallejas', 'Limpieza dental', EstadoCita::CANCELADA],
            [1, 4, '08:30', '5987412', 'gcallejas', 'Carilla de porcelana', EstadoCita::ATENDIDA],
            [1, 4, '14:30', '6721098', 'gcallejas', 'Destartraje periodontal', EstadoCita::ATENDIDA],
            [1, 4, '16:30', '10254789', 'rcalizaya', 'Sellante de fosas y fisuras', EstadoCita::ATENDIDA],
            [1, 5, '09:00', '9922341', 'rcalizaya', 'Ortodoncia con brackets', EstadoCita::ATENDIDA],
            [1, 5, '10:30', '7009812', 'gcallejas', 'Blanqueamiento dental', EstadoCita::ATENDIDA],

            [2, 0, '08:00', '9874561', 'rcalizaya', 'Ortodoncia con brackets', EstadoCita::ATENDIDA],
            [2, 0, '09:30', '4123698', 'gcallejas', 'Implante dental unitario', EstadoCita::ATENDIDA],
            [2, 0, '15:00', '8845201', 'gcallejas', 'Carilla de porcelana', EstadoCita::ATENDIDA],
            [2, 1, '10:00', '7845213', 'gcallejas', 'Endodoncia unirradicular', EstadoCita::ATENDIDA],
            [2, 1, '15:30', '10334567', 'rcalizaya', 'Aplicación de flúor', EstadoCita::ATENDIDA],
            [2, 2, '08:30', '5423891', 'rcalizaya', 'Corona de porcelana', EstadoCita::ATENDIDA],
            [2, 2, '11:00', '7156430', 'gcallejas', 'Blanqueamiento dental', EstadoCita::ATENDIDA],
            [2, 2, '16:00', '4876520', 'rcalizaya', 'Limpieza dental', EstadoCita::CANCELADA],
            [2, 3, '09:00', '6512478', 'gcallejas', 'Limpieza dental', EstadoCita::ATENDIDA],
            [2, 3, '14:30', '5310987', 'gcallejas', 'Implante dental unitario', EstadoCita::ATENDIDA],
            [2, 3, '16:30', '10112233', 'rcalizaya', 'Aplicación de flúor', EstadoCita::ATENDIDA],
            [2, 4, '08:00', '4498123', 'gcallejas', 'Prótesis parcial removible', EstadoCita::ATENDIDA],
            [2, 4, '10:00', '6634512', 'gcallejas', 'Corona de porcelana', EstadoCita::ATENDIDA],
            [2, 4, '15:00', '8123457', 'rcalizaya', 'Resina compuesta', EstadoCita::ATENDIDA],
            [2, 5, '09:00', '9013452', 'rcalizaya', 'Sellante de fosas y fisuras', EstadoCita::ATENDIDA],
            [2, 5, '10:00', '8123098', 'gcallejas', 'Limpieza dental', EstadoCita::ATENDIDA],
        ];

        foreach ($citas as [$semana, $dia, $hora, $ci, $usuario, $nombreTratamiento, $estadoPasado]) {
            $fecha = $lunes->copy()->addWeeks($semana)->addDays($dia);

            $estado = match (true) {
                $estadoPasado === EstadoCita::CANCELADA => EstadoCita::CANCELADA,
                $fecha->lt(today()) => $estadoPasado,
                default => EstadoCita::PROGRAMADA,
            };

            // Solo se ofrecen tratamientos disponibles, igual que en CU10.
            $tratamiento = Tratamiento::where('nombre', $nombreTratamiento)
                ->where('estado', 'disponible')
                ->firstOrFail();

            $datos = [
                // Paciente y odontólogo se buscan por CI y usuario, nunca con un id escrito a mano.
                'paciente_id' => Paciente::where('ci', $ci)->where('estado', 'activo')->firstOrFail()->id,
                'odontologo_id' => User::where('usuario', $usuario)->firstOrFail()->id,
                'tratamiento_aplicado_id' => null,
                'fecha' => $fecha->toDateString(),
                'hora_inicio' => $hora,
                'duracion_minutos' => $tratamiento->duracion_estimada_minutos,
                'motivo' => $tratamiento->nombre,
                'estado_cita_id' => EstadoCita::idDe($estado),
                'observaciones' => $estado === EstadoCita::CANCELADA ? 'Cancelada: el paciente avisó que no podía asistir.' : null,
                'fecha_registro' => $lunes->copy()->subDays(7)->toDateString(),
            ];

            // Una cancelada no ocupa el sillón; las demás no pueden cruzarse con otra cita.
            if ($estado !== EstadoCita::CANCELADA) {
                $this->verificarSillonLibre($datos['fecha'], $hora, $datos['duracion_minutos']);
            }

            Cita::create($datos);
        }
    }

    /**
     * RN-07 (un solo sillón): falla si la franja se cruza con una cita programada o
     * atendida del mismo día, sea de este seeder o de CitaSeeder. Hay cruce si
     * nuevoInicio < finExistente Y nuevoFin > inicioExistente.
     */
    private function verificarSillonLibre(string $fecha, string $hora, int $duracion): void
    {
        $inicio = Carbon::parse("$fecha $hora");
        $fin = $inicio->copy()->addMinutes($duracion);

        $conflicto = Cita::where('fecha', $fecha)
            ->whereIn('estado_cita_id', [EstadoCita::idDe(EstadoCita::PROGRAMADA), EstadoCita::idDe(EstadoCita::ATENDIDA)])
            ->where('hora_inicio', '<', $fin->format('H:i:s'))
            ->where('hora_fin', '>', $inicio->format('H:i:s'))
            ->first();

        if ($conflicto) {
            throw new RuntimeException("AgendaActualSeeder: la cita del {$fecha} a las {$hora} se cruza con la cita {$conflicto->id} ({$conflicto->hora_inicio} a {$conflicto->hora_fin}).");
        }
    }
}
