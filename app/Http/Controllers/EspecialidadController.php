<?php

namespace App\Http\Controllers;

use App\Models\Especialidad;
use App\Models\Tratamiento;
use App\Services\BitacoraService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CU14 Gestionar especialidades (catálogo, RF-28). Solo para el administrador.
 */
class EspecialidadController extends Controller
{
    // MOSTRAR la lista de especialidades, con la cantidad de usuarios de cada una.
    public function index(): View
    {
        $especialidades = Especialidad::withCount('usuarios')
            ->orderBy('nombre')
            ->get();

        return view('especialidades.index', compact('especialidades'));
    }

    // CREAR una especialidad.
    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate($this->reglas(), $this->mensajes());

        DB::transaction(function () use ($datos, $request) {
            $especialidad = Especialidad::create($datos);

            BitacoraService::registrar(BitacoraService::CREAR_ESPECIALIDAD, $request->user()->id, 'especialidades', $especialidad->id);
        });

        return redirect()->back()->with('exito', 'Especialidad registrada correctamente.');
    }

    // MOSTRAR UNA especialidad, con los usuarios que la tienen.
    public function show($id): View
    {
        $especialidad = Especialidad::withCount('usuarios')
            ->with(['usuarios' => fn ($consulta) => $consulta->orderBy('apellidos')->orderBy('nombres')])
            ->findOrFail($id);

        return view('especialidades.show', compact('especialidad'));
    }

    // MODIFICAR una especialidad. El id viene en el formulario.
    public function update(Request $request): RedirectResponse
    {
        // Primero se valida el id: se usa luego en ignore(), que no debe recibir datos sin validar.
        $request->validate($this->reglaId(), $this->mensajes());

        // El nombre debe ser único, sin contar a la propia especialidad.
        $datos = $request->validate($this->reglas((int) $request->input('id')), $this->mensajes());

        $especialidad = Especialidad::findOrFail($request->input('id'));

        DB::transaction(function () use ($especialidad, $datos, $request) {
            $especialidad->update($datos);

            BitacoraService::registrar(BitacoraService::MODIFICAR_ESPECIALIDAD, $request->user()->id, 'especialidades', $especialidad->id);
        });

        return redirect()->back()->with('exito', 'Especialidad actualizada correctamente.');
    }

    // ELIMINAR una especialidad, solo si nadie la usa. El id viene en el formulario.
    public function delete(Request $request): RedirectResponse
    {
        $request->validate($this->reglaId(), $this->mensajes());

        $especialidad = Especialidad::findOrFail($request->input('id'));

        // Se revisan los usuarios y también los tratamientos: la llave foránea
        // tratamientos.especialidad_id es RESTRICT y, sin esta verificación,
        // la base rechazaría el borrado con un error técnico.
        $tieneUsuarios = $especialidad->usuarios()->exists();
        $tieneTratamientos = Tratamiento::where('especialidad_id', $especialidad->id)->exists();

        if ($tieneUsuarios || $tieneTratamientos) {
            return redirect()->back()
                ->withErrors(['operacion' => 'No se puede eliminar la especialidad porque tiene usuarios o tratamientos asignados.']);
        }

        DB::transaction(function () use ($especialidad, $request) {
            // Se guarda el id antes de borrar para dejarlo en la bitácora.
            $idEliminado = $especialidad->id;
            $especialidad->delete();

            BitacoraService::registrar(BitacoraService::ELIMINAR_ESPECIALIDAD, $request->user()->id, 'especialidades', $idEliminado);
        });

        // Se vuelve al listado: si se eliminó desde la ficha, esa página ya no existe.
        return redirect()->route('especialidades.listar')->with('exito', 'Especialidad eliminada correctamente.');
    }

    // Regla del id que llega oculto en el formulario.
    private function reglaId(): array
    {
        return ['id' => ['required', 'integer', 'exists:especialidades,id']];
    }

    // Reglas de los datos; al modificar, la unicidad ignora a la propia especialidad.
    private function reglas(?int $idIgnorar = null): array
    {
        return [
            'nombre' => ['required', 'string', 'max:40', Rule::unique('especialidades', 'nombre')->ignore($idIgnorar)],
            'descripcion' => ['nullable', 'string'],
        ];
    }

    // Mensajes en español.
    private function mensajes(): array
    {
        return [
            'id.required' => 'No se indicó la especialidad.',
            'id.integer' => 'La especialidad indicada no es válida.',
            'id.exists' => 'La especialidad indicada no existe.',
            'nombre.required' => 'El campo Nombre es obligatorio.',
            'nombre.string' => 'El campo Nombre debe ser un texto.',
            'nombre.max' => 'El nombre no puede superar los 40 caracteres.',
            'nombre.unique' => 'Ya existe una especialidad con ese nombre.',
            'descripcion.string' => 'La descripción debe ser un texto.',
        ];
    }
}
