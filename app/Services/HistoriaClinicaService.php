<?php

namespace App\Services;

use App\Models\Control;
use App\Models\EstudioDiagnostico;
use App\Models\Odontograma;
use App\Models\Paciente;
use App\Models\PlanTratamiento;
use App\Models\ValoracionImplante;

/**
 * Clase de control "CTR HistoriaClinicaService" del análisis.
 *
 * Arma la historia clínica consolidada de CU24 (RF-13): todo lo clínico y financiero
 * del paciente en una sola consulta, con carga anticipada para no caer en N+1.
 * El estado de cuenta se calcula con PagoService, igual que en CU36.
 */
class HistoriaClinicaService
{
    public function __construct(private PagoService $pagos)
    {
    }

    /**
     * Devuelve los datos de la historia consolidada, o null si el paciente no tiene historia clínica.
     *
     * @return array{paciente: Paciente, historia: \App\Models\HistoriaClinica, ultimoOdontograma: ?Odontograma, totalOdontogramas: int, planes: \Illuminate\Support\Collection, estudios: \Illuminate\Support\Collection, valoraciones: \Illuminate\Support\Collection, controles: \Illuminate\Support\Collection, cuenta: array{costo_total: float, abonado: float, saldo: float}}|null
     */
    public function consolidar(Paciente $paciente): ?array
    {
        $historia = $paciente->historiaClinica()->with('antecedentes')->first();

        if (! $historia) {
            return null;
        }

        $odontogramas = Odontograma::where('historia_clinica_id', $historia->id);

        // Último odontograma activo, con el estado de cada pieza para la miniatura.
        $ultimoOdontograma = (clone $odontogramas)
            ->with('odontologo', 'detalles.estadoPieza')
            ->where('estado', 'activo')
            ->orderByDesc('fecha_registro')
            ->first();

        // Planes con sus tratamientos; sesiones_registradas cuenta las realizadas (avance «Sesión n de m»).
        $planes = PlanTratamiento::with([
            'odontologo',
            'tratamientosAplicados' => fn ($q) => $q->with('tratamiento', 'diagnostico', 'piezaDental')
                ->withCount(['sesiones as sesiones_registradas' => fn ($s) => $s->where('estado', 'realizada')])
                ->orderBy('fecha_inicio'),
        ])
            ->where('paciente_id', $paciente->id)
            ->orderByDesc('fecha_creacion')
            ->get();

        $estudios = EstudioDiagnostico::with('tipoEstudio', 'pieza')
            ->where('paciente_id', $paciente->id)
            ->orderByDesc('fecha_estudio')
            ->get();

        $valoraciones = ValoracionImplante::with('pieza')
            ->where('paciente_id', $paciente->id)
            ->orderByDesc('fecha_valoracion')
            ->get();

        // Controles del paciente: la tabla no tiene paciente_id, se llega por el tratamiento y su plan.
        $controles = Control::with('tratamientoAplicado.tratamiento')
            ->whereHas('tratamientoAplicado.plan', fn ($q) => $q->where('paciente_id', $paciente->id))
            ->orderByDesc('fecha_prevista')
            ->get();

        return [
            'paciente' => $paciente,
            'historia' => $historia,
            'ultimoOdontograma' => $ultimoOdontograma,
            'totalOdontogramas' => $odontogramas->count(),
            'planes' => $planes,
            'estudios' => $estudios,
            'valoraciones' => $valoraciones,
            'controles' => $controles,
            'cuenta' => $this->estadoCuenta($planes),
        ];
    }

    /**
     * Mismo criterio que CU36 Consultar estado de cuenta: los tratamientos cancelados no cuentan.
     *
     * @return array{costo_total: float, abonado: float, saldo: float}
     */
    private function estadoCuenta($planes): array
    {
        $tratamientos = $planes->flatMap->tratamientosAplicados->where('estado', '!=', 'cancelado');

        $costo = 0.0;
        $saldo = 0.0;
        foreach ($tratamientos as $tratamiento) {
            $costo += (float) $tratamiento->costo_acordado;
            $saldo += $this->pagos->saldoPendiente($tratamiento);
        }

        return [
            'costo_total' => round($costo, 2),
            'abonado' => round($costo - $saldo, 2),
            'saldo' => round($saldo, 2),
        ];
    }
}
