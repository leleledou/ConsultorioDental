<?php

namespace App\Http\Controllers;

use App\Models\DetalleOdontograma;
use App\Models\Odontograma;
use App\Models\TratamientoAplicado;
use App\Services\BitacoraService;
use App\Services\OdontogramaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * CU25 Registrar odontograma: estado de cada pieza del odontograma (RF-20, RF-23).
 * Para los dos roles (el administrador también es odontólogo).
 * El detalle no tiene estado: quitar una pieza la borra del odontograma.
 * Un odontograma anulado ya no se modifica.
 */
class DetalleOdontogramaController extends Controller
{
    public function __construct(private OdontogramaService $odontogramas)
    {
    }

    // CREAR: agrega al odontograma una pieza que todavía no está.
    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'odontograma_id' => ['required', 'integer', 'exists:odontogramas,id'],
            'pieza_dental_id' => ['required', 'integer', 'exists:piezas_dentales,id'],
        ] + $this->reglas(), $this->mensajes());

        $error = DB::transaction(function () use ($datos, $request) {
            $odontograma = Odontograma::with('historiaClinica')->lockForUpdate()->findOrFail($datos['odontograma_id']);

            if ($error = $this->validarOdontograma($odontograma, $datos['tratamiento_aplicado_id'] ?? null)) {
                return $error;
            }

            if (! $this->odontogramas->verificarPiezaUnica($odontograma->id, $datos['pieza_dental_id'])) {
                return 'Esta pieza ya está registrada en el odontograma.';
            }

            $detalle = DetalleOdontograma::create($datos);

            BitacoraService::registrar(BitacoraService::REGISTRAR_DETALLE_ODONTOGRAMA, $request->user()->id, 'detalles_odontograma', $detalle->id);

            return null;
        });

        if ($error) {
            return $this->error($error);
        }

        return redirect()->back()->with('exito', 'Estado de la pieza registrado correctamente.');
    }

    // MODIFICAR el estado, la observación y el tratamiento vinculado de una pieza. El id viene en el formulario.
    public function update(Request $request): RedirectResponse
    {
        $request->validate($this->reglaId(), $this->mensajes());

        $datos = $request->validate($this->reglas(), $this->mensajes());

        $error = DB::transaction(function () use ($datos, $request) {
            $detalle = DetalleOdontograma::findOrFail($request->input('id'));
            $odontograma = Odontograma::with('historiaClinica')->lockForUpdate()->findOrFail($detalle->odontograma_id);

            if ($error = $this->validarOdontograma($odontograma, $datos['tratamiento_aplicado_id'] ?? null)) {
                return $error;
            }

            $detalle->update([
                'estado_pieza_id' => $datos['estado_pieza_id'],
                'tratamiento_aplicado_id' => $datos['tratamiento_aplicado_id'] ?? null,
                'observacion' => $datos['observacion'] ?? null,
            ]);

            BitacoraService::registrar(BitacoraService::MODIFICAR_DETALLE_ODONTOGRAMA, $request->user()->id, 'detalles_odontograma', $detalle->id);

            return null;
        });

        if ($error) {
            return $this->error($error);
        }

        return redirect()->back()->with('exito', 'Estado de la pieza actualizado correctamente.');
    }

    // QUITAR una pieza del odontograma (borrado físico). El id viene en el formulario.
    public function delete(Request $request): RedirectResponse
    {
        $request->validate($this->reglaId(), $this->mensajes());

        $error = DB::transaction(function () use ($request) {
            $detalle = DetalleOdontograma::findOrFail($request->input('id'));
            $odontograma = Odontograma::lockForUpdate()->findOrFail($detalle->odontograma_id);

            if ($odontograma->estado === 'anulado') {
                return 'No se puede modificar un odontograma anulado.';
            }

            $detalle->delete();

            BitacoraService::registrar(BitacoraService::QUITAR_DETALLE_ODONTOGRAMA, $request->user()->id, 'detalles_odontograma', $detalle->id);

            return null;
        });

        if ($error) {
            return $this->error($error);
        }

        return redirect()->back()->with('exito', 'Pieza quitada del odontograma.');
    }

    // Reglas de negocio comunes: odontograma no anulado y tratamiento del mismo paciente (RF-20).
    private function validarOdontograma(Odontograma $odontograma, ?int $tratamientoAplicadoId): ?string
    {
        if ($odontograma->estado === 'anulado') {
            return 'No se puede modificar un odontograma anulado.';
        }

        if ($tratamientoAplicadoId !== null) {
            $delPaciente = TratamientoAplicado::where('id', $tratamientoAplicadoId)
                ->whereHas('plan', fn ($q) => $q->where('paciente_id', $odontograma->historiaClinica->paciente_id))
                ->exists();

            if (! $delPaciente) {
                return 'El tratamiento aplicado no pertenece al paciente del odontograma.';
            }
        }

        return null;
    }

    // Vuelve atrás con un error de negocio (clave "operacion").
    private function error(string $mensaje): RedirectResponse
    {
        return redirect()->back()->withInput()->withErrors(['operacion' => $mensaje]);
    }

    // Regla del id que llega oculto en el formulario.
    private function reglaId(): array
    {
        return ['id' => ['required', 'integer', 'exists:detalles_odontograma,id']];
    }

    // Reglas de los datos de la pieza: estado activo del catálogo y tratamiento opcional.
    private function reglas(): array
    {
        return [
            'estado_pieza_id' => ['required', 'integer', Rule::exists('estados_pieza', 'id')->where('estado', 'activo')],
            'tratamiento_aplicado_id' => ['nullable', 'integer', 'exists:tratamientos_aplicados,id'],
            'observacion' => ['nullable', 'string'],
        ];
    }

    // Mensajes en español.
    private function mensajes(): array
    {
        return [
            'id.required' => 'No se indicó la pieza del odontograma.',
            'id.integer' => 'La pieza del odontograma indicada no es válida.',
            'id.exists' => 'La pieza del odontograma indicada no existe.',
            'odontograma_id.required' => 'No se indicó el odontograma.',
            'odontograma_id.integer' => 'El odontograma indicado no es válido.',
            'odontograma_id.exists' => 'El odontograma indicado no existe.',
            'pieza_dental_id.required' => 'Debe seleccionar la pieza dental.',
            'pieza_dental_id.integer' => 'La pieza dental indicada no es válida.',
            'pieza_dental_id.exists' => 'La pieza dental indicada no existe.',
            'estado_pieza_id.required' => 'Debe seleccionar el estado de la pieza.',
            'estado_pieza_id.integer' => 'El estado de pieza indicado no es válido.',
            'estado_pieza_id.exists' => 'El estado de pieza indicado no existe o está dado de baja.',
            'tratamiento_aplicado_id.integer' => 'El tratamiento aplicado indicado no es válido.',
            'tratamiento_aplicado_id.exists' => 'El tratamiento aplicado indicado no existe.',
            'observacion.string' => 'La observación debe ser un texto.',
        ];
    }
}
