<?php

namespace App\Services;

use App\Models\DetalleOdontograma;
use App\Models\EstadoPieza;
use App\Models\Odontograma;
use App\Models\PiezaDental;
use Illuminate\Database\Eloquent\Collection;

/**
 * Clase de control "CTR OdontogramaService" del análisis.
 *
 * Reúne las reglas de las piezas del odontograma que comparten CU25 Registrar
 * odontograma (carga automática de piezas, RF-15) y CU26 Comparar odontogramas.
 */
class OdontogramaService
{
    /**
     * Código del estado de pieza con el que se cargan las piezas al crear el odontograma.
     */
    public const ESTADO_INICIAL = 'SANO';

    /**
     * Piezas que corresponden a un tipo de dentición, ordenadas por id (código FDI).
     * 'permanente' = 32 piezas, 'temporal' = 20 piezas, 'mixta' = las 52.
     */
    public function piezasPorDenticion(string $tipo): Collection
    {
        return PiezaDental::when($tipo !== 'mixta', fn ($consulta) => $consulta->where('tipo_denticion', $tipo))
            ->orderBy('id')
            ->get();
    }

    /**
     * RF-15: carga en el odontograma todas las piezas de su tipo de dentición, en estado SANO.
     * Devuelve la cantidad de piezas cargadas. Debe llamarse dentro de la transacción del que lo usa.
     */
    public function cargarPiezas(Odontograma $odontograma): int
    {
        $sano = EstadoPieza::where('codigo', self::ESTADO_INICIAL)->firstOrFail();

        $filas = $this->piezasPorDenticion($odontograma->tipo_denticion)
            ->map(fn (PiezaDental $pieza) => [
                'odontograma_id' => $odontograma->id,
                'pieza_dental_id' => $pieza->id,
                'estado_pieza_id' => $sano->id,
            ])
            ->all();

        DetalleOdontograma::insert($filas);

        return count($filas);
    }

    /**
     * Devuelve true si la pieza todavía NO está en el odontograma (uq_detalles_odontograma).
     */
    public function verificarPiezaUnica(int $odontogramaId, int $piezaId): bool
    {
        return ! DetalleOdontograma::where('odontograma_id', $odontogramaId)
            ->where('pieza_dental_id', $piezaId)
            ->exists();
    }

    /**
     * CU26: una fila por cada pieza presente en cualquiera de los dos odontogramas,
     * ordenadas por id de pieza. "cambio" es true si el estado difiere o si la pieza
     * falta en uno de los dos.
     *
     * @return list<array{pieza_id: int, codigo_fdi: int, nombre: string, cuadrante: int, estado_a: ?string, color_a: ?string, estado_b: ?string, color_b: ?string, cambio: bool}>
     */
    public function comparar(int $odontogramaA, int $odontogramaB): array
    {
        $a = $this->detallesPorPieza($odontogramaA);
        $b = $this->detallesPorPieza($odontogramaB);

        $piezas = $a->keys()->merge($b->keys())->unique()->sort()->values();

        $filas = [];
        foreach ($piezas as $piezaId) {
            $detalleA = $a->get($piezaId);
            $detalleB = $b->get($piezaId);
            $pieza = ($detalleA ?? $detalleB)->pieza;

            $filas[] = [
                'pieza_id' => $pieza->id,
                'codigo_fdi' => $pieza->codigo_fdi,
                'nombre' => $pieza->nombre,
                'cuadrante' => $pieza->cuadrante,
                'estado_a' => $detalleA?->estadoPieza->nombre,
                'color_a' => $detalleA?->estadoPieza->color_referencia,
                'estado_b' => $detalleB?->estadoPieza->nombre,
                'color_b' => $detalleB?->estadoPieza->color_referencia,
                'cambio' => $detalleA === null || $detalleB === null
                    || (int) $detalleA->estado_pieza_id !== (int) $detalleB->estado_pieza_id,
            ];
        }

        return $filas;
    }

    // Detalles de un odontograma con su pieza y estado, indexados por id de pieza.
    private function detallesPorPieza(int $odontogramaId): Collection
    {
        return DetalleOdontograma::with('pieza', 'estadoPieza')
            ->where('odontograma_id', $odontogramaId)
            ->get()
            ->keyBy('pieza_dental_id');
    }
}
