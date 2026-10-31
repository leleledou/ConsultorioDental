<?php

namespace App\Http\Controllers;

use App\Models\MetodoPago;
use App\Services\BitacoraService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CU37 Gestionar métodos de pago. Solo para el administrador.
 * Nunca se borran del catálogo (RNF-06): "eliminar" significa dar de baja.
 */
class MetodoPagoController extends Controller
{
    // MOSTRAR la lista de métodos de pago, con buscador por nombre.
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

        $metodosPago = MetodoPago::when($buscar, fn ($consulta, $buscar) => $consulta->where('nombre', 'like', "%{$buscar}%"))
            ->orderBy('nombre')
            ->paginate(10)
            // Conserva ?buscar=... en los enlaces de las páginas.
            ->withQueryString();

        return view('metodos-pago.index', compact('metodosPago', 'buscar'));
    }

    // CREAR un método de pago; queda activo.
    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate($this->reglas(), $this->mensajes());

        DB::transaction(function () use ($datos, $request) {
            $metodoPago = MetodoPago::create($datos + ['estado' => 'activo']);

            BitacoraService::registrar(BitacoraService::CREAR_METODO_PAGO, $request->user()->id, 'metodos_pago', $metodoPago->id);
        });

        return redirect()->back()->with('exito', 'Método de pago registrado correctamente.');
    }

    // MOSTRAR UN método de pago.
    public function show($id): View
    {
        $metodoPago = MetodoPago::findOrFail($id);

        return view('metodos-pago.show', compact('metodoPago'));
    }

    // MODIFICAR un método de pago. El id viene en el formulario. No cambia el estado.
    public function update(Request $request): RedirectResponse
    {
        // Primero se valida el id: se usa luego en ignore(), que no debe recibir datos sin validar.
        $request->validate($this->reglaId(), $this->mensajes());

        // El nombre debe ser único, sin contar al propio método de pago.
        $datos = $request->validate($this->reglas((int) $request->input('id')), $this->mensajes());

        $metodoPago = MetodoPago::findOrFail($request->input('id'));

        DB::transaction(function () use ($metodoPago, $datos, $request) {
            $metodoPago->update($datos);

            BitacoraService::registrar(BitacoraService::MODIFICAR_METODO_PAGO, $request->user()->id, 'metodos_pago', $metodoPago->id);
        });

        return redirect()->back()->with('exito', 'Método de pago actualizado correctamente.');
    }

    // ELIMINAR = DAR DE BAJA un método de pago (RNF-06): estado "inactivo", sin borrarlo.
    public function delete(Request $request): RedirectResponse
    {
        $request->validate($this->reglaId(), $this->mensajes());

        $metodoPago = MetodoPago::findOrFail($request->input('id'));

        if ($metodoPago->estado === 'inactivo') {
            return $this->error('El método de pago ya está dado de baja.');
        }

        DB::transaction(function () use ($metodoPago, $request) {
            $metodoPago->estado = 'inactivo';
            $metodoPago->save();

            BitacoraService::registrar(BitacoraService::BAJA_METODO_PAGO, $request->user()->id, 'metodos_pago', $metodoPago->id);
        });

        return redirect()->back()->with('exito', 'Método de pago dado de baja.');
    }

    // HABILITAR un método de pago dado de baja: vuelve a estar activo.
    public function activar(Request $request): RedirectResponse
    {
        $request->validate($this->reglaId(), $this->mensajes());

        $metodoPago = MetodoPago::findOrFail($request->input('id'));

        if ($metodoPago->estado === 'activo') {
            return $this->error('El método de pago ya está activo.');
        }

        DB::transaction(function () use ($metodoPago, $request) {
            $metodoPago->estado = 'activo';
            $metodoPago->save();

            BitacoraService::registrar(BitacoraService::HABILITAR_METODO_PAGO, $request->user()->id, 'metodos_pago', $metodoPago->id);
        });

        return redirect()->back()->with('exito', 'Método de pago habilitado.');
    }

    // Vuelve atrás con un error de negocio (clave "operacion").
    private function error(string $mensaje): RedirectResponse
    {
        return redirect()->back()->withErrors(['operacion' => $mensaje]);
    }

    // Regla del id que llega oculto en el formulario.
    private function reglaId(): array
    {
        return ['id' => ['required', 'integer', 'exists:metodos_pago,id']];
    }

    // Reglas de los datos; al modificar, la unicidad ignora al propio método de pago.
    private function reglas(?int $idIgnorar = null): array
    {
        return [
            'nombre' => ['required', 'string', 'max:30', Rule::unique('metodos_pago', 'nombre')->ignore($idIgnorar)],
            'descripcion' => ['nullable', 'string'],
        ];
    }

    // Mensajes en español.
    private function mensajes(): array
    {
        return [
            'id.required' => 'No se indicó el método de pago.',
            'id.integer' => 'El método de pago indicado no es válido.',
            'id.exists' => 'El método de pago indicado no existe.',
            'nombre.required' => 'El campo Nombre es obligatorio.',
            'nombre.string' => 'El campo Nombre debe ser un texto.',
            'nombre.max' => 'El nombre no puede superar los 30 caracteres.',
            'nombre.unique' => 'Ya existe un método de pago con ese nombre.',
            'descripcion.string' => 'La descripción debe ser un texto.',
        ];
    }
}
