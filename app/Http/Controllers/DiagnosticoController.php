<?php

namespace App\Http\Controllers;

use App\Models\Diagnostico;
use App\Services\BitacoraService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CU17 Gestionar diagnósticos. Solo para el administrador.
 * Nunca se borran del catálogo (RNF-06): "eliminar" significa dar de baja.
 */
class DiagnosticoController extends Controller
{
    // MOSTRAR la lista de diagnósticos, con buscador por nombre o código.
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

        // Los orWhere van agrupados en un solo bloque entre paréntesis.
        $diagnosticos = Diagnostico::when($buscar, function ($consulta, $buscar) {
            $consulta->where(function ($grupo) use ($buscar) {
                $grupo->where('nombre', 'like', "%{$buscar}%")
                    ->orWhere('codigo', 'like', "%{$buscar}%");
            });
        })
            ->orderBy('nombre')
            ->paginate(10)
            // Conserva ?buscar=... en los enlaces de las páginas.
            ->withQueryString();

        return view('diagnosticos.index', compact('diagnosticos', 'buscar'));
    }

    // CREAR un diagnóstico; queda activo.
    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate($this->reglas(), $this->mensajes());

        DB::transaction(function () use ($datos, $request) {
            $diagnostico = Diagnostico::create($datos + ['estado' => 'activo']);

            BitacoraService::registrar(BitacoraService::CREAR_DIAGNOSTICO, $request->user()->id, 'diagnosticos', $diagnostico->id);
        });

        return redirect()->back()->with('exito', 'Diagnóstico registrado correctamente.');
    }

    // MOSTRAR UN diagnóstico.
    public function show($id): View
    {
        $diagnostico = Diagnostico::findOrFail($id);

        return view('diagnosticos.show', compact('diagnostico'));
    }

    // MODIFICAR un diagnóstico. El id viene en el formulario. No cambia el estado.
    public function update(Request $request): RedirectResponse
    {
        // Primero se valida el id: se usa luego en ignore(), que no debe recibir datos sin validar.
        $request->validate($this->reglaId(), $this->mensajes());

        // El código debe ser único, sin contar al propio diagnóstico.
        $datos = $request->validate($this->reglas((int) $request->input('id')), $this->mensajes());

        $diagnostico = Diagnostico::findOrFail($request->input('id'));

        DB::transaction(function () use ($diagnostico, $datos, $request) {
            $diagnostico->update($datos);

            BitacoraService::registrar(BitacoraService::MODIFICAR_DIAGNOSTICO, $request->user()->id, 'diagnosticos', $diagnostico->id);
        });

        return redirect()->back()->with('exito', 'Diagnóstico actualizado correctamente.');
    }

    // ELIMINAR = DAR DE BAJA un diagnóstico (RNF-06): estado "inactivo", sin borrarlo.
    public function delete(Request $request): RedirectResponse
    {
        $request->validate($this->reglaId(), $this->mensajes());

        $diagnostico = Diagnostico::findOrFail($request->input('id'));

        if ($diagnostico->estado === 'inactivo') {
            return $this->error('El diagnóstico ya está dado de baja.');
        }

        DB::transaction(function () use ($diagnostico, $request) {
            $diagnostico->estado = 'inactivo';
            $diagnostico->save();

            BitacoraService::registrar(BitacoraService::BAJA_DIAGNOSTICO, $request->user()->id, 'diagnosticos', $diagnostico->id);
        });

        return redirect()->back()->with('exito', 'Diagnóstico dado de baja.');
    }

    // HABILITAR un diagnóstico dado de baja: vuelve a estar activo.
    public function activar(Request $request): RedirectResponse
    {
        $request->validate($this->reglaId(), $this->mensajes());

        $diagnostico = Diagnostico::findOrFail($request->input('id'));

        if ($diagnostico->estado === 'activo') {
            return $this->error('El diagnóstico ya está activo.');
        }

        DB::transaction(function () use ($diagnostico, $request) {
            $diagnostico->estado = 'activo';
            $diagnostico->save();

            BitacoraService::registrar(BitacoraService::HABILITAR_DIAGNOSTICO, $request->user()->id, 'diagnosticos', $diagnostico->id);
        });

        return redirect()->back()->with('exito', 'Diagnóstico habilitado.');
    }

    // Vuelve atrás con un error de negocio (clave "operacion").
    private function error(string $mensaje): RedirectResponse
    {
        return redirect()->back()->withErrors(['operacion' => $mensaje]);
    }

    // Regla del id que llega oculto en el formulario.
    private function reglaId(): array
    {
        return ['id' => ['required', 'integer', 'exists:diagnosticos,id']];
    }

    // Reglas de los datos; al modificar, la unicidad ignora al propio diagnóstico.
    private function reglas(?int $idIgnorar = null): array
    {
        return [
            'codigo' => ['required', 'string', 'max:10', Rule::unique('diagnosticos', 'codigo')->ignore($idIgnorar)],
            'nombre' => ['required', 'string', 'max:60'],
            'descripcion' => ['nullable', 'string'],
        ];
    }

    // Mensajes en español.
    private function mensajes(): array
    {
        return [
            'id.required' => 'No se indicó el diagnóstico.',
            'id.integer' => 'El diagnóstico indicado no es válido.',
            'id.exists' => 'El diagnóstico indicado no existe.',
            'codigo.required' => 'El campo Código es obligatorio.',
            'codigo.string' => 'El campo Código debe ser un texto.',
            'codigo.max' => 'El código no puede superar los 10 caracteres.',
            'codigo.unique' => 'Ya existe un diagnóstico con ese código.',
            'nombre.required' => 'El campo Nombre es obligatorio.',
            'nombre.string' => 'El campo Nombre debe ser un texto.',
            'nombre.max' => 'El nombre no puede superar los 60 caracteres.',
            'descripcion.string' => 'La descripción debe ser un texto.',
        ];
    }
}
