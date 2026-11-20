<?php

namespace App\Services;

use App\Models\EstudioDiagnostico;
use App\Models\TratamientoAplicado;
use App\Models\TratamientoTipoEstudio;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Clase de control "CTR EstudioDiagnosticoService" del análisis.
 *
 * Reúne las reglas de los estudios diagnósticos de CU32 (RF-52 a RF-57, RF-59) y el criterio
 * de "estudio cargado" que también usa CU28 (RF-39). La columna ruta_archivo guarda la ruta
 * del archivo subido o la URL del centro radiológico: se distinguen porque la URL empieza con http.
 */
class EstudioDiagnosticoService
{
    /**
     * Disco y carpeta donde se guardan los archivos de los estudios.
     */
    public const DISCO = 'public';

    public const CARPETA = 'estudios';

    /**
     * Guarda el archivo en el disco público (carpeta estudios) y devuelve su ruta.
     */
    public function guardarArchivo(UploadedFile $archivo): string
    {
        return $archivo->store(self::CARPETA, self::DISCO);
    }

    /**
     * true si la ruta es un enlace externo (http:// o https://) y no un archivo del disco.
     */
    public function esEnlace(?string $ruta): bool
    {
        return $ruta !== null && preg_match('#^https?://#i', $ruta) === 1;
    }

    /**
     * Borra del disco el archivo de un estudio. Un enlace o una ruta vacía no se tocan.
     */
    public function borrarArchivo(?string $ruta): void
    {
        if ($ruta && ! $this->esEnlace($ruta)) {
            Storage::disk(self::DISCO)->delete($ruta);
        }
    }

    /**
     * Tipos de estudio que el paciente ya tiene cargados (cualquier fecha).
     * Es el mismo criterio de RF-39 (CU28) y de RF-59.
     */
    public function tiposCargados(int $pacienteId): Collection
    {
        return EstudioDiagnostico::where('paciente_id', $pacienteId)->pluck('tipo_estudio_id')->unique()->values();
    }

    /**
     * RF-59: estudios que exige el tratamiento del tratamiento aplicado, separados en los que
     * el paciente ya tiene cargados (con el estudio más reciente) y los que siguen pendientes.
     *
     * @return array{cargados: list<array{tipo_estudio_id: int, nombre: string, obligatorio: bool, momento_clinico: string, observacion: ?string, estudio: ?EstudioDiagnostico}>, pendientes: list<array{tipo_estudio_id: int, nombre: string, obligatorio: bool, momento_clinico: string, observacion: ?string, estudio: null}>}
     */
    public function requeridosPendientes(TratamientoAplicado $ta): array
    {
        $pacienteId = $ta->plan()->value('paciente_id');

        $requeridos = TratamientoTipoEstudio::with('tipoEstudio')
            ->where('tratamiento_id', $ta->tratamiento_id)
            ->orderByDesc('obligatorio')
            ->get();

        // Estudio más reciente de cada tipo exigido, para enlazarlo desde la ficha.
        $estudios = EstudioDiagnostico::where('paciente_id', $pacienteId)
            ->whereIn('tipo_estudio_id', $requeridos->pluck('tipo_estudio_id'))
            ->orderByDesc('fecha_estudio')
            ->orderByDesc('id')
            ->get()
            ->unique('tipo_estudio_id')
            ->keyBy('tipo_estudio_id');

        $resultado = ['cargados' => [], 'pendientes' => []];

        foreach ($requeridos as $requerido) {
            $estudio = $estudios->get($requerido->tipo_estudio_id);

            $resultado[$estudio ? 'cargados' : 'pendientes'][] = [
                'tipo_estudio_id' => $requerido->tipo_estudio_id,
                'nombre' => $requerido->tipoEstudio->nombre,
                'obligatorio' => (bool) $requerido->obligatorio,
                'momento_clinico' => $requerido->momento_clinico,
                'observacion' => $requerido->observacion,
                'estudio' => $estudio,
            ];
        }

        return $resultado;
    }
}
