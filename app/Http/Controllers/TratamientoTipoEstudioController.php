<?php

namespace App\Http\Controllers;

use App\Models\TipoEstudio;
use App\Models\Tratamiento;
use App\Models\TratamientoTipoEstudio;
use App\Services\BitacoraService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CU19 Definir estudios requeridos por tratamiento (RF-31). Solo para el administrador.
 * Quitar un requerido no toca los estudios ya cargados de los pacientes.
 */
class TratamientoTipoEstudioController extends Controller
{
    // MOSTRAR los estudios requeridos de UN tratamiento y los tipos activos para el formulario.
    public function index($id): View
    {
        $tratamiento = Tratamiento::findOrFail($id);

        $requeridos = TratamientoTipoEstudio::with('tipoEstudio')
            ->where('tratamiento_id', $id)
            ->get();

        $tipos = TipoEstudio::where('estado', 'activo')->get();

        return view('tratamientos.estudios', compact('tratamiento', 'requeridos', 'tipos'));
    }

    // ASIGNAR un estudio requerido al tratamiento.
    public function store(Request $request): RedirectResponse
    {
        // Flujo A1: tipo o momento sin seleccionar.
        $datos = $request->validate($this->reglas(), $this->mensajes());

        // Flujo A2 (RF-31): el tipo ya está definido para el tratamiento.
        if ($this->estaRepetido($datos)) {
            return $this->rechazarRepetido();
        }

        DB::transaction(function () use ($datos, $request) {
            $requerido = TratamientoTipoEstudio::create($datos);

            BitacoraService::registrar(BitacoraService::ASIGNAR_ESTUDIO_REQUERIDO, $request->user()->id, 'tratamientos_tipos_estudio', $requerido->id);
        });

        return redirect()->back()->with('exito', 'Estudio requerido guardado.');
    }

    // MODIFICAR un estudio requerido. El id viene en el formulario.
    public function update(Request $request): RedirectResponse
    {
        $request->validate($this->reglaId(), $this->mensajes());

        // Flujo A1: tipo o momento sin seleccionar.
        $datos = $request->validate($this->reglas(), $this->mensajes());

        // Flujo A2 (RF-31): sin contar al propio registro.
        if ($this->estaRepetido($datos, (int) $request->input('id'))) {
            return $this->rechazarRepetido();
        }

        DB::transaction(function () use ($datos, $request) {
            $requerido = TratamientoTipoEstudio::findOrFail($request->input('id'));
            $requerido->update($datos);

            BitacoraService::registrar(BitacoraService::MODIFICAR_ESTUDIO_REQUERIDO, $request->user()->id, 'tratamientos_tipos_estudio', $requerido->id);
        });

        return redirect()->back()->with('exito', 'Estudio requerido guardado.');
    }

    // QUITAR un estudio requerido del tratamiento. El id viene en el formulario.
    public function delete(Request $request): RedirectResponse
    {
        $request->validate($this->reglaId(), $this->mensajes());

        DB::transaction(function () use ($request) {
            // Se guarda el id antes de borrar para dejarlo en la bitácora.
            $idEliminado = (int) $request->input('id');
            TratamientoTipoEstudio::findOrFail($idEliminado)->delete();

            BitacoraService::registrar(BitacoraService::QUITAR_ESTUDIO_REQUERIDO, $request->user()->id, 'tratamientos_tipos_estudio', $idEliminado);
        });

        return redirect()->back()->with('exito', 'Estudio requerido quitado.');
    }

    // RF-31: indica si el tipo ya está definido para el tratamiento.
    private function estaRepetido(array $datos, ?int $idExcluir = null): bool
    {
        return TratamientoTipoEstudio::where('tratamiento_id', $datos['tratamiento_id'])
            ->where('tipo_estudio_id', $datos['tipo_estudio_id'])
            ->when($idExcluir, fn ($consulta) => $consulta->where('id', '!=', $idExcluir))
            ->exists();
    }

    // Vuelve al formulario con el error del flujo A2; no se guarda nada.
    private function rechazarRepetido(): RedirectResponse
    {
        return redirect()->back()
            ->withErrors(['tipo_estudio_id' => 'Este tipo de estudio ya está definido para el tratamiento'])
            ->withInput();
    }

    // Regla del id que llega oculto en el formulario.
    private function reglaId(): array
    {
        return ['id' => ['required', 'integer', 'exists:tratamientos_tipos_estudio,id']];
    }

    // Reglas de los datos.
    private function reglas(): array
    {
        return [
            'tratamiento_id' => ['required', 'integer', 'exists:tratamientos,id'],
            'tipo_estudio_id' => ['required', 'integer', 'exists:tipos_estudio,id'],
            'obligatorio' => ['boolean'],
            'momento_clinico' => ['required', Rule::in(TratamientoTipoEstudio::MOMENTOS_CLINICOS)],
            'observacion' => ['nullable', 'string'],
        ];
    }

    // Mensajes en español.
    private function mensajes(): array
    {
        return [
            'id.required' => 'No se indicó el estudio requerido.',
            'id.integer' => 'El estudio requerido indicado no es válido.',
            'id.exists' => 'El estudio requerido indicado no existe.',
            'tratamiento_id.required' => 'No se indicó el tratamiento.',
            'tratamiento_id.integer' => 'El tratamiento indicado no es válido.',
            'tratamiento_id.exists' => 'El tratamiento indicado no existe.',
            'tipo_estudio_id.required' => 'Tipo de estudio o momento clínico sin seleccionar',
            'tipo_estudio_id.integer' => 'El tipo de estudio indicado no es válido.',
            'tipo_estudio_id.exists' => 'El tipo de estudio indicado no existe.',
            'obligatorio.boolean' => 'El campo Obligatorio debe ser sí o no.',
            'momento_clinico.required' => 'Tipo de estudio o momento clínico sin seleccionar',
            'momento_clinico.in' => 'El momento clínico indicado no es válido.',
            'observacion.string' => 'La observación debe ser un texto.',
        ];
    }
}
