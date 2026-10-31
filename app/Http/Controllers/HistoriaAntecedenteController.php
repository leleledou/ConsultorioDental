<?php

namespace App\Http\Controllers;

use App\Models\HistoriaAntecedente;
use App\Services\BitacoraService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * CU23 Gestionar historia clínica: antecedentes médicos de la historia.
 * Para los dos roles (el administrador también es odontólogo).
 */
class HistoriaAntecedenteController extends Controller
{
    // ASIGNAR un antecedente del catálogo a la historia, con la fecha de hoy.
    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate($this->reglas(), $this->mensajes());

        // Flujo A1: el antecedente ya está en la historia.
        $repetido = HistoriaAntecedente::where('historia_clinica_id', $datos['historia_clinica_id'])
            ->where('antecedente_medico_id', $datos['antecedente_medico_id'])
            ->exists();

        if ($repetido) {
            return redirect()->back()
                ->withErrors(['antecedente_medico_id' => 'Antecedente ya registrado en la historia clínica'])
                ->withInput();
        }

        DB::transaction(function () use ($datos, $request) {
            $asignado = HistoriaAntecedente::create($datos + ['fecha_registro' => today()]);

            BitacoraService::registrar(BitacoraService::ASIGNAR_ANTECEDENTE, $request->user()->id, 'historias_antecedentes', $asignado->id);
        });

        return redirect()->back()->with('exito', 'Antecedente asignado.');
    }

    // MODIFICAR la observación de un antecedente de la historia. El id viene en el formulario.
    public function update(Request $request): RedirectResponse
    {
        $datos = $request->validate($this->reglaId() + [
            'observacion' => ['nullable', 'string'],
        ], $this->mensajes());

        DB::transaction(function () use ($datos, $request) {
            $asignado = HistoriaAntecedente::findOrFail($datos['id']);
            $asignado->update(['observacion' => $datos['observacion'] ?? null]);

            BitacoraService::registrar(BitacoraService::MODIFICAR_ANTECEDENTE_HISTORIA, $request->user()->id, 'historias_antecedentes', $asignado->id);
        });

        return redirect()->back()->with('exito', 'Historia clínica actualizada.');
    }

    // QUITAR un antecedente de la historia. El id viene en el formulario.
    public function delete(Request $request): RedirectResponse
    {
        $request->validate($this->reglaId(), $this->mensajes());

        DB::transaction(function () use ($request) {
            // Se guarda el id antes de borrar para dejarlo en la bitácora.
            $idEliminado = (int) $request->input('id');
            HistoriaAntecedente::findOrFail($idEliminado)->delete();

            BitacoraService::registrar(BitacoraService::QUITAR_ANTECEDENTE, $request->user()->id, 'historias_antecedentes', $idEliminado);
        });

        return redirect()->back()->with('exito', 'Historia clínica actualizada.');
    }

    // Regla del id que llega oculto en el formulario.
    private function reglaId(): array
    {
        return ['id' => ['required', 'integer', 'exists:historias_antecedentes,id']];
    }

    // Reglas de los datos.
    private function reglas(): array
    {
        return [
            'historia_clinica_id' => ['required', 'integer', 'exists:historias_clinicas,id'],
            'antecedente_medico_id' => ['required', 'integer', 'exists:antecedentes_medicos,id'],
            'observacion' => ['nullable', 'string'],
        ];
    }

    // Mensajes en español.
    private function mensajes(): array
    {
        return [
            'id.required' => 'No se indicó el antecedente de la historia.',
            'id.integer' => 'El antecedente de la historia indicado no es válido.',
            'id.exists' => 'El antecedente de la historia indicado no existe.',
            'historia_clinica_id.required' => 'No se indicó la historia clínica.',
            'historia_clinica_id.integer' => 'La historia clínica indicada no es válida.',
            'historia_clinica_id.exists' => 'La historia clínica indicada no existe.',
            'antecedente_medico_id.required' => 'Debe seleccionar el antecedente.',
            'antecedente_medico_id.integer' => 'El antecedente indicado no es válido.',
            'antecedente_medico_id.exists' => 'El antecedente indicado no existe.',
            'observacion.string' => 'La observación debe ser un texto.',
        ];
    }
}
