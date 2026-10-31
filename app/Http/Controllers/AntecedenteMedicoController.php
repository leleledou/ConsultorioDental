<?php

namespace App\Http\Controllers;

use App\Models\AntecedenteMedico;
use App\Models\HistoriaAntecedente;
use App\Services\BitacoraService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CU21 Gestionar antecedentes médicos. Solo para el administrador.
 * El catálogo no tiene estado: un antecedente sin historias se elimina de verdad.
 */
class AntecedenteMedicoController extends Controller
{
    // MOSTRAR la lista de antecedentes, con la cantidad de historias de cada uno y buscador por nombre.
    public function index(Request $request): View
    {
        $request->validate(
            ['buscar' => ['nullable', 'string', 'max:100']],
            [
                'buscar.string' => 'El texto de búsqueda no es válido.',
                'buscar.max' => 'El texto de búsqueda no puede superar los 100 caracteres.',
            ]
        );

        $buscar = $request->input('buscar');

        $antecedentes = AntecedenteMedico::withCount('historias')
            ->when($buscar, fn ($consulta, $buscar) => $consulta->where('nombre', 'like', "%{$buscar}%"))
            ->orderBy('nombre')
            ->paginate(10)
            // Conserva ?buscar=... en los enlaces de las páginas.
            ->withQueryString();

        return view('antecedentes.index', compact('antecedentes', 'buscar'));
    }

    // CREAR un antecedente.
    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate($this->reglas(), $this->mensajes());

        DB::transaction(function () use ($datos, $request) {
            $antecedente = AntecedenteMedico::create($datos);

            BitacoraService::registrar(BitacoraService::CREAR_ANTECEDENTE, $request->user()->id, 'antecedentes_medicos', $antecedente->id);
        });

        return redirect()->back()->with('exito', 'Antecedente registrado correctamente.');
    }

    // MOSTRAR UN antecedente.
    public function show($id): View
    {
        $antecedente = AntecedenteMedico::findOrFail($id);

        return view('antecedentes.show', compact('antecedente'));
    }

    // MODIFICAR un antecedente. El id viene en el formulario.
    public function update(Request $request): RedirectResponse
    {
        // Primero se valida el id: se usa luego en ignore(), que no debe recibir datos sin validar.
        $request->validate($this->reglaId(), $this->mensajes());

        // El nombre debe ser único, sin contar al propio antecedente.
        $datos = $request->validate($this->reglas((int) $request->input('id')), $this->mensajes());

        $antecedente = AntecedenteMedico::findOrFail($request->input('id'));

        DB::transaction(function () use ($antecedente, $datos, $request) {
            $antecedente->update($datos);

            BitacoraService::registrar(BitacoraService::MODIFICAR_ANTECEDENTE, $request->user()->id, 'antecedentes_medicos', $antecedente->id);
        });

        return redirect()->back()->with('exito', 'Antecedente actualizado correctamente.');
    }

    // ELIMINAR un antecedente, solo si ninguna historia clínica lo usa. El id viene en el formulario.
    public function delete(Request $request): RedirectResponse
    {
        $request->validate($this->reglaId(), $this->mensajes());

        $n = HistoriaAntecedente::where('antecedente_medico_id', $request->input('id'))->count();

        if ($n > 0) {
            return $this->error("No se puede eliminar: el antecedente está registrado en {$n} historias clínicas");
        }

        DB::transaction(function () use ($request) {
            // Se guarda el id antes de borrar para dejarlo en la bitácora.
            $idEliminado = (int) $request->input('id');
            AntecedenteMedico::findOrFail($idEliminado)->delete();

            BitacoraService::registrar(BitacoraService::ELIMINAR_ANTECEDENTE, $request->user()->id, 'antecedentes_medicos', $idEliminado);
        });

        // Se vuelve al listado: si se eliminó desde la ficha, esa página ya no existe.
        return redirect()->route('antecedentes.listar')->with('exito', 'Antecedente eliminado.');
    }

    // Vuelve atrás con un error de negocio (clave "operacion").
    private function error(string $mensaje): RedirectResponse
    {
        return redirect()->back()->withErrors(['operacion' => $mensaje]);
    }

    // Regla del id que llega oculto en el formulario.
    private function reglaId(): array
    {
        return ['id' => ['required', 'integer', 'exists:antecedentes_medicos,id']];
    }

    // Reglas de los datos; al modificar, la unicidad ignora al propio antecedente.
    private function reglas(?int $idIgnorar = null): array
    {
        return [
            'nombre' => ['required', 'string', 'max:50', Rule::unique('antecedentes_medicos', 'nombre')->ignore($idIgnorar)],
            'descripcion' => ['nullable', 'string'],
        ];
    }

    // Mensajes en español.
    private function mensajes(): array
    {
        return [
            'id.required' => 'No se indicó el antecedente.',
            'id.integer' => 'El antecedente indicado no es válido.',
            'id.exists' => 'El antecedente indicado no existe.',
            'nombre.required' => 'El campo Nombre es obligatorio.',
            'nombre.string' => 'El campo Nombre debe ser un texto.',
            'nombre.max' => 'El nombre no puede superar los 50 caracteres.',
            'nombre.unique' => 'Ya existe un antecedente con ese nombre.',
            'descripcion.string' => 'La descripción debe ser un texto.',
        ];
    }
}
