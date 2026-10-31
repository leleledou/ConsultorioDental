<?php

namespace App\Http\Controllers;

use App\Models\TipoEstudio;
use App\Services\BitacoraService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CU18 Gestionar tipos de estudio. Solo para el administrador.
 * Nunca se borran del catálogo (RNF-06): "eliminar" significa dar de baja.
 */
class TipoEstudioController extends Controller
{
    // MOSTRAR la lista de tipos de estudio, con buscador por nombre.
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

        $tiposEstudio = TipoEstudio::when($buscar, fn ($consulta, $buscar) => $consulta->where('nombre', 'like', "%{$buscar}%"))
            ->orderBy('nombre')
            ->paginate(10)
            // Conserva ?buscar=... en los enlaces de las páginas.
            ->withQueryString();

        return view('tipos-estudio.index', compact('tiposEstudio', 'buscar'));
    }

    // CREAR un tipo de estudio; queda activo.
    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate($this->reglas(), $this->mensajes());

        DB::transaction(function () use ($datos, $request) {
            $tipoEstudio = TipoEstudio::create($datos + ['estado' => 'activo']);

            BitacoraService::registrar(BitacoraService::CREAR_TIPO_ESTUDIO, $request->user()->id, 'tipos_estudio', $tipoEstudio->id);
        });

        return redirect()->back()->with('exito', 'Tipo de estudio registrado correctamente.');
    }

    // MOSTRAR UN tipo de estudio.
    public function show($id): View
    {
        $tipoEstudio = TipoEstudio::findOrFail($id);

        return view('tipos-estudio.show', compact('tipoEstudio'));
    }

    // MODIFICAR un tipo de estudio. El id viene en el formulario. No cambia el estado.
    public function update(Request $request): RedirectResponse
    {
        // Primero se valida el id: se usa luego en ignore(), que no debe recibir datos sin validar.
        $request->validate($this->reglaId(), $this->mensajes());

        // El nombre debe ser único, sin contar al propio tipo de estudio.
        $datos = $request->validate($this->reglas((int) $request->input('id')), $this->mensajes());

        $tipoEstudio = TipoEstudio::findOrFail($request->input('id'));

        DB::transaction(function () use ($tipoEstudio, $datos, $request) {
            $tipoEstudio->update($datos);

            BitacoraService::registrar(BitacoraService::MODIFICAR_TIPO_ESTUDIO, $request->user()->id, 'tipos_estudio', $tipoEstudio->id);
        });

        return redirect()->back()->with('exito', 'Tipo de estudio actualizado correctamente.');
    }

    // ELIMINAR = DAR DE BAJA un tipo de estudio (RNF-06): estado "inactivo", sin borrarlo.
    public function delete(Request $request): RedirectResponse
    {
        $request->validate($this->reglaId(), $this->mensajes());

        $tipoEstudio = TipoEstudio::findOrFail($request->input('id'));

        if ($tipoEstudio->estado === 'inactivo') {
            return $this->error('El tipo de estudio ya está dado de baja.');
        }

        DB::transaction(function () use ($tipoEstudio, $request) {
            $tipoEstudio->estado = 'inactivo';
            $tipoEstudio->save();

            BitacoraService::registrar(BitacoraService::BAJA_TIPO_ESTUDIO, $request->user()->id, 'tipos_estudio', $tipoEstudio->id);
        });

        return redirect()->back()->with('exito', 'Tipo de estudio dado de baja.');
    }

    // HABILITAR un tipo de estudio dado de baja: vuelve a estar activo.
    public function activar(Request $request): RedirectResponse
    {
        $request->validate($this->reglaId(), $this->mensajes());

        $tipoEstudio = TipoEstudio::findOrFail($request->input('id'));

        if ($tipoEstudio->estado === 'activo') {
            return $this->error('El tipo de estudio ya está activo.');
        }

        DB::transaction(function () use ($tipoEstudio, $request) {
            $tipoEstudio->estado = 'activo';
            $tipoEstudio->save();

            BitacoraService::registrar(BitacoraService::HABILITAR_TIPO_ESTUDIO, $request->user()->id, 'tipos_estudio', $tipoEstudio->id);
        });

        return redirect()->back()->with('exito', 'Tipo de estudio habilitado.');
    }

    // Vuelve atrás con un error de negocio (clave "operacion").
    private function error(string $mensaje): RedirectResponse
    {
        return redirect()->back()->withErrors(['operacion' => $mensaje]);
    }

    // Regla del id que llega oculto en el formulario.
    private function reglaId(): array
    {
        return ['id' => ['required', 'integer', 'exists:tipos_estudio,id']];
    }

    // Reglas de los datos; al modificar, la unicidad ignora al propio tipo de estudio.
    private function reglas(?int $idIgnorar = null): array
    {
        return [
            'nombre' => ['required', 'string', 'max:50', Rule::unique('tipos_estudio', 'nombre')->ignore($idIgnorar)],
            'descripcion' => ['nullable', 'string'],
        ];
    }

    // Mensajes en español.
    private function mensajes(): array
    {
        return [
            'id.required' => 'No se indicó el tipo de estudio.',
            'id.integer' => 'El tipo de estudio indicado no es válido.',
            'id.exists' => 'El tipo de estudio indicado no existe.',
            'nombre.required' => 'El campo Nombre es obligatorio.',
            'nombre.string' => 'El campo Nombre debe ser un texto.',
            'nombre.max' => 'El nombre no puede superar los 50 caracteres.',
            'nombre.unique' => 'Ya existe un tipo de estudio con ese nombre.',
            'descripcion.string' => 'La descripción debe ser un texto.',
        ];
    }
}
