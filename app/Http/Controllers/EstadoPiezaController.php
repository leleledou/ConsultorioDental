<?php

namespace App\Http\Controllers;

use App\Models\EstadoPieza;
use App\Services\BitacoraService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CU22 Gestionar piezas dentales y estados de pieza. Solo para el administrador.
 * Nunca se borran del catálogo (RNF-06): "eliminar" significa dar de baja.
 */
class EstadoPiezaController extends Controller
{
    // MOSTRAR la lista de estados de pieza, con buscador por nombre.
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

        $estadosPieza = EstadoPieza::when($buscar, fn ($consulta, $buscar) => $consulta->where('nombre', 'like', "%{$buscar}%"))
            ->orderBy('nombre')
            ->paginate(10)
            // Conserva ?buscar=... en los enlaces de las páginas.
            ->withQueryString();

        return view('estados-pieza.index', compact('estadosPieza', 'buscar'));
    }

    // CREAR un estado de pieza; queda activo.
    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate($this->reglas(), $this->mensajes());

        DB::transaction(function () use ($datos, $request) {
            $estadoPieza = EstadoPieza::create($datos + ['estado' => 'activo']);

            BitacoraService::registrar(BitacoraService::CREAR_ESTADO_PIEZA, $request->user()->id, 'estados_pieza', $estadoPieza->id);
        });

        return redirect()->back()->with('exito', 'Estado de pieza registrado correctamente.');
    }

    // MOSTRAR UN estado de pieza.
    public function show($id): View
    {
        $estadoPieza = EstadoPieza::findOrFail($id);

        return view('estados-pieza.show', compact('estadoPieza'));
    }

    // MODIFICAR un estado de pieza. El id viene en el formulario. No cambia el estado.
    public function update(Request $request): RedirectResponse
    {
        // Primero se valida el id: se usa luego en ignore(), que no debe recibir datos sin validar.
        $request->validate($this->reglaId(), $this->mensajes());

        // El código debe ser único, sin contar al propio estado de pieza.
        $datos = $request->validate($this->reglas((int) $request->input('id')), $this->mensajes());

        $estadoPieza = EstadoPieza::findOrFail($request->input('id'));

        DB::transaction(function () use ($estadoPieza, $datos, $request) {
            $estadoPieza->update($datos);

            BitacoraService::registrar(BitacoraService::MODIFICAR_ESTADO_PIEZA, $request->user()->id, 'estados_pieza', $estadoPieza->id);
        });

        return redirect()->back()->with('exito', 'Estado de pieza actualizado correctamente.');
    }

    // ELIMINAR = DAR DE BAJA un estado de pieza (RNF-06): estado "inactivo", sin borrarlo.
    public function delete(Request $request): RedirectResponse
    {
        $request->validate($this->reglaId(), $this->mensajes());

        $estadoPieza = EstadoPieza::findOrFail($request->input('id'));

        if ($estadoPieza->estado === 'inactivo') {
            return $this->error('El estado de pieza ya está dado de baja.');
        }

        DB::transaction(function () use ($estadoPieza, $request) {
            $estadoPieza->estado = 'inactivo';
            $estadoPieza->save();

            BitacoraService::registrar(BitacoraService::BAJA_ESTADO_PIEZA, $request->user()->id, 'estados_pieza', $estadoPieza->id);
        });

        return redirect()->back()->with('exito', 'Estado de pieza dado de baja.');
    }

    // HABILITAR un estado de pieza dado de baja: vuelve a estar activo.
    public function activar(Request $request): RedirectResponse
    {
        $request->validate($this->reglaId(), $this->mensajes());

        $estadoPieza = EstadoPieza::findOrFail($request->input('id'));

        if ($estadoPieza->estado === 'activo') {
            return $this->error('El estado de pieza ya está activo.');
        }

        DB::transaction(function () use ($estadoPieza, $request) {
            $estadoPieza->estado = 'activo';
            $estadoPieza->save();

            BitacoraService::registrar(BitacoraService::HABILITAR_ESTADO_PIEZA, $request->user()->id, 'estados_pieza', $estadoPieza->id);
        });

        return redirect()->back()->with('exito', 'Estado de pieza habilitado.');
    }

    // Vuelve atrás con un error de negocio (clave "operacion").
    private function error(string $mensaje): RedirectResponse
    {
        return redirect()->back()->withErrors(['operacion' => $mensaje]);
    }

    // Regla del id que llega oculto en el formulario.
    private function reglaId(): array
    {
        return ['id' => ['required', 'integer', 'exists:estados_pieza,id']];
    }

    // Reglas de los datos; al modificar, la unicidad ignora al propio estado de pieza.
    private function reglas(?int $idIgnorar = null): array
    {
        return [
            'codigo' => ['required', 'string', 'max:10', Rule::unique('estados_pieza', 'codigo')->ignore($idIgnorar)],
            'nombre' => ['required', 'string', 'max:40'],
            'descripcion' => ['nullable', 'string'],
            'color_referencia' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }

    // Mensajes en español.
    private function mensajes(): array
    {
        return [
            'id.required' => 'No se indicó el estado de pieza.',
            'id.integer' => 'El estado de pieza indicado no es válido.',
            'id.exists' => 'El estado de pieza indicado no existe.',
            'codigo.required' => 'El campo Código es obligatorio.',
            'codigo.string' => 'El campo Código debe ser un texto.',
            'codigo.max' => 'El código no puede superar los 10 caracteres.',
            'codigo.unique' => 'Ya existe un estado de pieza con ese código.',
            'nombre.required' => 'El campo Nombre es obligatorio.',
            'nombre.string' => 'El campo Nombre debe ser un texto.',
            'nombre.max' => 'El nombre no puede superar los 40 caracteres.',
            'descripcion.string' => 'La descripción debe ser un texto.',
            'color_referencia.string' => 'El campo Color de referencia debe ser un texto.',
            'color_referencia.regex' => 'El color de referencia debe tener el formato hexadecimal #RRGGBB.',
        ];
    }
}
