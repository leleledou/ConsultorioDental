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
 * CU15 Gestionar catálogo de tratamientos (RF-25). Solo para el administrador.
 * Nunca se borran tratamientos (RNF-06): "eliminar" significa dar de baja.
 */
class TratamientoController extends Controller
{
    // MOSTRAR la lista de tratamientos, con buscador por nombre o especialidad.
    public function index(Request $request): View
    {
        $request->validate(
            ['buscar' => ['nullable', 'string', 'max:100']],
            ['buscar.max' => 'El texto de búsqueda no puede superar los 100 caracteres.']
        );

        $buscar = $request->input('buscar');

        // Los orWhere van agrupados en un solo bloque entre paréntesis.
        $tratamientos = Tratamiento::with('especialidad')
            ->when($buscar, function ($consulta, $buscar) {
                $consulta->where(function ($grupo) use ($buscar) {
                    $grupo->where('nombre', 'like', "%{$buscar}%")
                        ->orWhereHas('especialidad', fn ($e) => $e->where('nombre', 'like', "%{$buscar}%"));
                });
            })
            ->orderBy('nombre')
            ->paginate(10)
            // Conserva ?buscar=... en los enlaces de las páginas.
            ->withQueryString();

        $especialidades = Especialidad::orderBy('nombre')->get(['id', 'nombre']);

        return view('tratamientos.index', compact('tratamientos', 'especialidades', 'buscar'));
    }

    // CREAR un tratamiento del catálogo; queda disponible (RF-25).
    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate($this->reglas(), $this->mensajes());

        DB::transaction(function () use ($datos, $request) {
            $tratamiento = Tratamiento::create($datos + ['estado' => 'disponible']);

            BitacoraService::registrar(BitacoraService::CREAR_TRATAMIENTO, $request->user()->id, 'tratamientos', $tratamiento->id);
        });

        return redirect()->back()->with('exito', 'Tratamiento registrado correctamente.');
    }

    // MOSTRAR UN tratamiento con su especialidad.
    public function show($id): View
    {
        $tratamiento = Tratamiento::with('especialidad')->findOrFail($id);

        return view('tratamientos.show', compact('tratamiento'));
    }

    // MODIFICAR un tratamiento (RF-25). El id viene en el formulario. No cambia el estado.
    public function update(Request $request): RedirectResponse
    {
        // Primero se valida el id: se usa luego en ignore(), que no debe recibir datos sin validar.
        $request->validate($this->reglaId(), $this->mensajes());

        // El nombre debe ser único, sin contar al propio tratamiento.
        $datos = $request->validate($this->reglas((int) $request->input('id')), $this->mensajes());

        $tratamiento = Tratamiento::findOrFail($request->input('id'));

        DB::transaction(function () use ($tratamiento, $datos, $request) {
            $tratamiento->update($datos);

            BitacoraService::registrar(BitacoraService::MODIFICAR_TRATAMIENTO, $request->user()->id, 'tratamientos', $tratamiento->id);
        });

        return redirect()->back()->with('exito', 'Tratamiento actualizado correctamente.');
    }

    // ELIMINAR = DAR DE BAJA un tratamiento (RNF-06): estado "no disponible", sin borrarlo.
    public function delete(Request $request): RedirectResponse
    {
        $request->validate($this->reglaId(), $this->mensajes());

        $tratamiento = Tratamiento::findOrFail($request->input('id'));

        if (! $tratamiento->estaDisponible()) {
            return $this->error('El tratamiento ya está dado de baja.');
        }

        DB::transaction(function () use ($tratamiento, $request) {
            $tratamiento->estado = 'no disponible';
            $tratamiento->save();

            BitacoraService::registrar(BitacoraService::BAJA_TRATAMIENTO, $request->user()->id, 'tratamientos', $tratamiento->id);
        });

        return redirect()->back()->with('exito', 'Tratamiento dado de baja. Ya no se ofrecerá al programar citas.');
    }

    // HABILITAR un tratamiento dado de baja: vuelve a estar disponible.
    public function activar(Request $request): RedirectResponse
    {
        $request->validate($this->reglaId(), $this->mensajes());

        $tratamiento = Tratamiento::findOrFail($request->input('id'));

        if ($tratamiento->estaDisponible()) {
            return $this->error('El tratamiento ya está disponible.');
        }

        DB::transaction(function () use ($tratamiento, $request) {
            $tratamiento->estado = 'disponible';
            $tratamiento->save();

            BitacoraService::registrar(BitacoraService::HABILITAR_TRATAMIENTO, $request->user()->id, 'tratamientos', $tratamiento->id);
        });

        return redirect()->back()->with('exito', 'Tratamiento habilitado correctamente.');
    }

    // Vuelve atrás con un error de negocio (clave "operacion").
    private function error(string $mensaje): RedirectResponse
    {
        return redirect()->back()->withErrors(['operacion' => $mensaje]);
    }

    // Regla del id que llega oculto en el formulario.
    private function reglaId(): array
    {
        return ['id' => ['required', 'integer', 'exists:tratamientos,id']];
    }

    // Reglas de los datos; al modificar, la unicidad ignora al propio tratamiento.
    private function reglas(?int $idIgnorar = null): array
    {
        return [
            'nombre' => ['required', 'string', 'max:60', Rule::unique('tratamientos', 'nombre')->ignore($idIgnorar)],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'especialidad_id' => ['required', 'integer', 'exists:especialidades,id'],
            // Límite superior: la columna es SMALLINT sin signo.
            'duracion_estimada_minutos' => ['required', 'integer', 'min:1', 'max:600'],
            'numero_sesiones_previstas' => ['required', 'integer', 'min:1', 'max:100'],
            'requiere_sesiones' => ['required', 'boolean'],
        ];
    }

    // Mensajes en español.
    private function mensajes(): array
    {
        return [
            'id.required' => 'No se indicó el tratamiento.',
            'id.integer' => 'El tratamiento indicado no es válido.',
            'id.exists' => 'El tratamiento indicado no existe.',
            'nombre.required' => 'El campo Nombre es obligatorio.',
            'nombre.string' => 'El campo Nombre debe ser un texto.',
            'nombre.max' => 'El nombre no puede superar los 60 caracteres.',
            'nombre.unique' => 'Ya existe un tratamiento con ese nombre.',
            'descripcion.string' => 'La descripción debe ser un texto.',
            'descripcion.max' => 'La descripción no puede superar los 1000 caracteres.',
            'especialidad_id.required' => 'Debe seleccionar la especialidad.',
            'especialidad_id.integer' => 'La especialidad indicada no es válida.',
            'especialidad_id.exists' => 'La especialidad indicada no existe.',
            'duracion_estimada_minutos.required' => 'El campo Duración estimada es obligatorio.',
            'duracion_estimada_minutos.integer' => 'La duración estimada debe ser un número entero de minutos.',
            'duracion_estimada_minutos.min' => 'La duración estimada debe ser mayor a 0 minutos.',
            'duracion_estimada_minutos.max' => 'La duración estimada no puede superar los 600 minutos.',
            'numero_sesiones_previstas.required' => 'El campo Sesiones previstas es obligatorio.',
            'numero_sesiones_previstas.integer' => 'Las sesiones previstas deben ser un número entero.',
            'numero_sesiones_previstas.min' => 'Debe haber al menos 1 sesión prevista.',
            'numero_sesiones_previstas.max' => 'Las sesiones previstas no pueden superar 100.',
            'requiere_sesiones.required' => 'Indique si el tratamiento requiere sesiones.',
            'requiere_sesiones.boolean' => 'El campo Requiere sesiones debe ser sí o no.',
        ];
    }
}
