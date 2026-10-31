<?php

namespace App\Http\Controllers;

use App\Models\AntecedenteMedico;
use App\Models\HistoriaClinica;
use App\Services\BitacoraService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * CU23 Gestionar historia clínica: hábitos y observaciones generales.
 * Para los dos roles (el administrador también es odontólogo).
 * La historia se abre al registrar al paciente y nunca se elimina (RF-10).
 */
class HistoriaClinicaController extends Controller
{
    // MOSTRAR la historia clínica de UN paciente, con el catálogo de antecedentes para asignar.
    public function show($paciente): View
    {
        $historia = HistoriaClinica::with('paciente', 'antecedentes')
            ->where('paciente_id', $paciente)
            ->firstOrFail();

        $catalogo = AntecedenteMedico::orderBy('nombre')->get();

        return view('historias.show', compact('historia', 'catalogo'));
    }

    // MODIFICAR los hábitos y las observaciones generales. El id viene en el formulario.
    public function update(Request $request): RedirectResponse
    {
        $request->validate($this->reglaId(), $this->mensajes());

        $datos = $request->validate($this->reglas(), $this->mensajes());

        DB::transaction(function () use ($datos, $request) {
            $historia = HistoriaClinica::findOrFail($request->input('id'));
            $historia->update($datos);

            BitacoraService::registrar(BitacoraService::MODIFICAR_HISTORIA, $request->user()->id, 'historias_clinicas', $historia->id);
        });

        return redirect()->back()->with('exito', 'Historia clínica actualizada.');
    }

    // Regla del id que llega oculto en el formulario.
    private function reglaId(): array
    {
        return ['id' => ['required', 'integer', 'exists:historias_clinicas,id']];
    }

    // Reglas de los datos.
    private function reglas(): array
    {
        return [
            'habitos' => ['nullable', 'string'],
            'observaciones_generales' => ['nullable', 'string'],
        ];
    }

    // Mensajes en español.
    private function mensajes(): array
    {
        return [
            'id.required' => 'No se indicó la historia clínica.',
            'id.integer' => 'La historia clínica indicada no es válida.',
            'id.exists' => 'La historia clínica indicada no existe.',
            'habitos.string' => 'Los hábitos deben ser un texto.',
            'observaciones_generales.string' => 'Las observaciones generales deben ser un texto.',
        ];
    }
}
