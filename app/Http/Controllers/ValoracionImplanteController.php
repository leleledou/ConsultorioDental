<?php

namespace App\Http\Controllers;

use App\Models\EstudioDiagnostico;
use App\Models\Paciente;
use App\Models\PiezaDental;
use App\Models\TratamientoAplicado;
use App\Models\ValoracionImplante;
use App\Services\BitacoraService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CU33 Registrar valoración para implante (RF-60 a RF-65).
 * Para los dos roles (el administrador también es odontólogo).
 * El estudio que la respalda y el tratamiento derivado son opcionales, pero deben ser del
 * mismo paciente. Una valoración no se elimina: se corrige con modificar.
 */
class ValoracionImplanteController extends Controller
{
    // MOSTRAR las valoraciones de UN paciente, de la más reciente a la más antigua.
    public function index($paciente): View
    {
        $paciente = Paciente::findOrFail($paciente);

        $valoraciones = ValoracionImplante::with('pieza', 'odontologo', 'estudio.tipoEstudio', 'tratamientoAplicado.tratamiento')
            ->where('paciente_id', $paciente->id)
            ->orderByDesc('fecha_valoracion')
            ->orderByDesc('id')
            ->get();

        return view('valoraciones.index', compact('paciente', 'valoraciones') + $this->datosFormulario($paciente));
    }

    // CREAR una valoración. El odontólogo es el de la sesión iniciada.
    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate($this->reglas(true), $this->mensajes());

        if ($error = $this->relacionesAjenas($datos, (int) $datos['paciente_id'])) {
            return $error;
        }

        $valoracion = DB::transaction(function () use ($datos, $request) {
            $valoracion = ValoracionImplante::create($this->columnas($datos) + [
                'paciente_id' => $datos['paciente_id'],
                'odontologo_id' => $request->user()->id,
            ]);

            BitacoraService::registrar(BitacoraService::REGISTRAR_VALORACION, $request->user()->id, 'valoraciones_implante', $valoracion->id);

            return $valoracion;
        });

        return redirect()->route('valoraciones.mostrar', $valoracion->id)
            ->with('exito', 'Valoración registrada correctamente.');
    }

    // MODIFICAR una valoración. El id viene en el formulario; el paciente no cambia.
    public function update(Request $request): RedirectResponse
    {
        $request->validate($this->reglaId(), $this->mensajes());

        $valoracion = ValoracionImplante::findOrFail($request->input('id'));

        $datos = $request->validate($this->reglas(false), $this->mensajes());

        if ($error = $this->relacionesAjenas($datos, (int) $valoracion->paciente_id)) {
            return $error;
        }

        DB::transaction(function () use ($valoracion, $datos, $request) {
            $valoracion->update($this->columnas($datos));

            BitacoraService::registrar(BitacoraService::MODIFICAR_VALORACION, $request->user()->id, 'valoraciones_implante', $valoracion->id);
        });

        return redirect()->route('valoraciones.mostrar', $valoracion->id)
            ->with('exito', 'Valoración actualizada correctamente.');
    }

    // MOSTRAR UNA valoración, con el formulario de modificar.
    public function show($id): View
    {
        $valoracion = ValoracionImplante::with('paciente', 'pieza', 'odontologo', 'estudio.tipoEstudio', 'tratamientoAplicado.tratamiento')
            ->findOrFail($id);

        return view('valoraciones.show', compact('valoracion') + $this->datosFormulario($valoracion->paciente));
    }

    // Catálogos del formulario: piezas, estudios y tratamientos aplicados del paciente.
    private function datosFormulario(Paciente $paciente): array
    {
        return [
            'piezas' => PiezaDental::orderBy('id')->get(),
            'estudios' => EstudioDiagnostico::with('tipoEstudio')
                ->where('paciente_id', $paciente->id)
                ->orderByDesc('fecha_estudio')
                ->get(),
            'tratamientos' => TratamientoAplicado::with('tratamiento')
                ->whereHas('plan', fn ($q) => $q->where('paciente_id', $paciente->id))
                ->orderByDesc('fecha_inicio')
                ->get(),
        ];
    }

    // RF-62 y RF-65: el estudio y el tratamiento indicados tienen que ser del mismo paciente.
    private function relacionesAjenas(array $datos, int $pacienteId): ?RedirectResponse
    {
        $errores = [];

        if (! empty($datos['estudio_diagnostico_id'])
            && ! EstudioDiagnostico::where('id', $datos['estudio_diagnostico_id'])->where('paciente_id', $pacienteId)->exists()) {
            $errores['estudio_diagnostico_id'] = 'El estudio diagnóstico no pertenece a este paciente.';
        }

        if (! empty($datos['tratamiento_aplicado_id'])
            && ! TratamientoAplicado::where('id', $datos['tratamiento_aplicado_id'])
                ->whereHas('plan', fn ($q) => $q->where('paciente_id', $pacienteId))
                ->exists()) {
            $errores['tratamiento_aplicado_id'] = 'El tratamiento aplicado no pertenece a este paciente.';
        }

        return $errores ? redirect()->back()->withInput()->withErrors($errores) : null;
    }

    // Columnas editables de la valoración. Sin la casilla marcada, requiere_injerto es false.
    private function columnas(array $datos): array
    {
        return [
            'pieza_dental_id' => $datos['pieza_dental_id'],
            'estudio_diagnostico_id' => $datos['estudio_diagnostico_id'] ?? null,
            'tratamiento_aplicado_id' => $datos['tratamiento_aplicado_id'] ?? null,
            'fecha_valoracion' => $datos['fecha_valoracion'],
            'estado_hueso' => $datos['estado_hueso'],
            'requiere_injerto' => (bool) ($datos['requiere_injerto'] ?? false),
            'observaciones_clinicas' => $datos['observaciones_clinicas'] ?? null,
            'resultado' => $datos['resultado'],
        ];
    }

    // Regla del id que llega oculto en el formulario.
    private function reglaId(): array
    {
        return ['id' => ['required', 'integer', 'exists:valoraciones_implante,id']];
    }

    // Reglas de los datos; al modificar, el paciente no viaja en el formulario.
    private function reglas(bool $alta): array
    {
        return [
            'paciente_id' => $alta ? ['required', 'integer', 'exists:pacientes,id'] : ['prohibited'],
            'pieza_dental_id' => ['required', 'integer', 'exists:piezas_dentales,id'],
            'estudio_diagnostico_id' => ['nullable', 'integer', 'exists:estudios_diagnosticos,id'],
            'tratamiento_aplicado_id' => ['nullable', 'integer', 'exists:tratamientos_aplicados,id'],
            'fecha_valoracion' => ['bail', 'required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'estado_hueso' => ['required', Rule::in(['suficiente', 'regular', 'insuficiente'])],
            'requiere_injerto' => ['nullable', 'boolean'],
            'observaciones_clinicas' => ['nullable', 'string'],
            'resultado' => ['required', Rule::in(['apto', 'apto con condiciones', 'no apto'])],
        ];
    }

    // Mensajes en español.
    private function mensajes(): array
    {
        return [
            'id.required' => 'No se indicó la valoración.',
            'id.integer' => 'La valoración indicada no es válida.',
            'id.exists' => 'La valoración indicada no existe.',
            'paciente_id.required' => 'No se indicó el paciente.',
            'paciente_id.integer' => 'El paciente indicado no es válido.',
            'paciente_id.exists' => 'El paciente indicado no existe.',
            'paciente_id.prohibited' => 'El paciente de una valoración no se puede cambiar.',
            'pieza_dental_id.required' => 'Seleccione la pieza dental.',
            'pieza_dental_id.integer' => 'La pieza dental indicada no es válida.',
            'pieza_dental_id.exists' => 'La pieza dental indicada no existe.',
            'estudio_diagnostico_id.integer' => 'El estudio diagnóstico indicado no es válido.',
            'estudio_diagnostico_id.exists' => 'El estudio diagnóstico indicado no existe.',
            'tratamiento_aplicado_id.integer' => 'El tratamiento aplicado indicado no es válido.',
            'tratamiento_aplicado_id.exists' => 'El tratamiento aplicado indicado no existe.',
            'fecha_valoracion.required' => 'El campo Fecha de la valoración es obligatorio.',
            'fecha_valoracion.date_format' => 'La fecha debe tener el formato AAAA-MM-DD.',
            'fecha_valoracion.before_or_equal' => 'La fecha de la valoración no puede ser futura.',
            'estado_hueso.required' => 'Seleccione el estado del hueso.',
            'estado_hueso.in' => 'El estado del hueso debe ser suficiente, regular o insuficiente.',
            'requiere_injerto.boolean' => 'El valor de Requiere injerto no es válido.',
            'observaciones_clinicas.string' => 'Las observaciones clínicas deben ser un texto.',
            'resultado.required' => 'Seleccione el resultado de la valoración.',
            'resultado.in' => 'El resultado debe ser apto, apto con condiciones o no apto.',
        ];
    }
}
