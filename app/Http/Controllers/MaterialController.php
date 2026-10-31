<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Services\BitacoraService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CU20 Gestionar materiales. Solo para el administrador.
 * Nunca se borran del catálogo (RNF-06): "eliminar" significa dar de baja.
 */
class MaterialController extends Controller
{
    // MOSTRAR la lista de materiales, con buscador por nombre.
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

        $materiales = Material::when($buscar, fn ($consulta, $buscar) => $consulta->where('nombre', 'like', "%{$buscar}%"))
            ->orderBy('nombre')
            ->paginate(10)
            // Conserva ?buscar=... en los enlaces de las páginas.
            ->withQueryString();

        return view('materiales.index', compact('materiales', 'buscar'));
    }

    // CREAR un material; queda activo.
    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate($this->reglas(), $this->mensajes());

        DB::transaction(function () use ($datos, $request) {
            $material = Material::create($datos + ['estado' => 'activo']);

            BitacoraService::registrar(BitacoraService::CREAR_MATERIAL, $request->user()->id, 'materiales', $material->id);
        });

        return redirect()->back()->with('exito', 'Material registrado correctamente.');
    }

    // MOSTRAR UN material.
    public function show($id): View
    {
        $material = Material::findOrFail($id);

        return view('materiales.show', compact('material'));
    }

    // MODIFICAR un material. El id viene en el formulario. No cambia el estado.
    public function update(Request $request): RedirectResponse
    {
        // Primero se valida el id: se usa luego en ignore(), que no debe recibir datos sin validar.
        $request->validate($this->reglaId(), $this->mensajes());

        // El nombre debe ser único, sin contar al propio material.
        $datos = $request->validate($this->reglas((int) $request->input('id')), $this->mensajes());

        $material = Material::findOrFail($request->input('id'));

        DB::transaction(function () use ($material, $datos, $request) {
            $material->update($datos);

            BitacoraService::registrar(BitacoraService::MODIFICAR_MATERIAL, $request->user()->id, 'materiales', $material->id);
        });

        return redirect()->back()->with('exito', 'Material actualizado correctamente.');
    }

    // ELIMINAR = DAR DE BAJA un material (RNF-06): estado "inactivo", sin borrarlo.
    public function delete(Request $request): RedirectResponse
    {
        $request->validate($this->reglaId(), $this->mensajes());

        $material = Material::findOrFail($request->input('id'));

        if ($material->estado === 'inactivo') {
            return $this->error('El material ya está dado de baja.');
        }

        DB::transaction(function () use ($material, $request) {
            $material->estado = 'inactivo';
            $material->save();

            BitacoraService::registrar(BitacoraService::BAJA_MATERIAL, $request->user()->id, 'materiales', $material->id);
        });

        return redirect()->back()->with('exito', 'Material dado de baja.');
    }

    // HABILITAR un material dado de baja: vuelve a estar activo.
    public function activar(Request $request): RedirectResponse
    {
        $request->validate($this->reglaId(), $this->mensajes());

        $material = Material::findOrFail($request->input('id'));

        if ($material->estado === 'activo') {
            return $this->error('El material ya está activo.');
        }

        DB::transaction(function () use ($material, $request) {
            $material->estado = 'activo';
            $material->save();

            BitacoraService::registrar(BitacoraService::HABILITAR_MATERIAL, $request->user()->id, 'materiales', $material->id);
        });

        return redirect()->back()->with('exito', 'Material habilitado.');
    }

    // Vuelve atrás con un error de negocio (clave "operacion").
    private function error(string $mensaje): RedirectResponse
    {
        return redirect()->back()->withErrors(['operacion' => $mensaje]);
    }

    // Regla del id que llega oculto en el formulario.
    private function reglaId(): array
    {
        return ['id' => ['required', 'integer', 'exists:materiales,id']];
    }

    // Reglas de los datos; al modificar, la unicidad ignora al propio material.
    private function reglas(?int $idIgnorar = null): array
    {
        return [
            'nombre' => ['required', 'string', 'max:60', Rule::unique('materiales', 'nombre')->ignore($idIgnorar)],
            'descripcion' => ['nullable', 'string'],
            'categoria' => ['required', 'string', 'max:20'],
            'unidad_medida' => ['required', 'string', 'max:15'],
        ];
    }

    // Mensajes en español.
    private function mensajes(): array
    {
        return [
            'id.required' => 'No se indicó el material.',
            'id.integer' => 'El material indicado no es válido.',
            'id.exists' => 'El material indicado no existe.',
            'nombre.required' => 'El campo Nombre es obligatorio.',
            'nombre.string' => 'El campo Nombre debe ser un texto.',
            'nombre.max' => 'El nombre no puede superar los 60 caracteres.',
            'nombre.unique' => 'Ya existe un material con ese nombre.',
            'descripcion.string' => 'La descripción debe ser un texto.',
            'categoria.required' => 'El campo Categoría es obligatorio.',
            'categoria.string' => 'El campo Categoría debe ser un texto.',
            'categoria.max' => 'La categoría no puede superar los 20 caracteres.',
            'unidad_medida.required' => 'El campo Unidad de medida es obligatorio.',
            'unidad_medida.string' => 'El campo Unidad de medida debe ser un texto.',
            'unidad_medida.max' => 'La unidad de medida no puede superar los 15 caracteres.',
        ];
    }
}
