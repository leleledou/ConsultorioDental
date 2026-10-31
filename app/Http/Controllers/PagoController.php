<?php

namespace App\Http\Controllers;

use App\Models\MetodoPago;
use App\Models\Paciente;
use App\Models\Pago;
use App\Models\PlanTratamiento;
use App\Models\TratamientoAplicado;
use App\Services\BitacoraService;
use App\Services\PagoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CU34 Registrar abono, CU35 Anular abono y CU36 Consultar estado de cuenta.
 * Para los dos roles (el administrador también es odontólogo).
 * Las reglas de los abonos están en PagoService. El saldo nunca se guarda (RF-75)
 * y un abono nunca se elimina (RNF-06): se anula.
 */
class PagoController extends Controller
{
    public function __construct(private PagoService $pagos)
    {
    }

    // CU36: MOSTRAR el estado de cuenta de UN paciente.
    public function estadoCuenta($paciente): View
    {
        $paciente = Paciente::findOrFail($paciente);

        $tratamientos = TratamientoAplicado::with('tratamiento', 'plan', 'pagos.metodoPago')
            ->whereHas('plan', fn ($q) => $q->where('paciente_id', $paciente->id))
            ->where('estado', '!=', 'cancelado')
            ->get();

        $saldos = [];
        $abonados = [];
        foreach ($tratamientos as $tratamiento) {
            $saldos[$tratamiento->id] = $this->pagos->saldoPendiente($tratamiento);
            $abonados[$tratamiento->id] = $tratamiento->costo_acordado - $saldos[$tratamiento->id];
        }

        return view('pagos.cuenta', [
            'paciente' => $paciente,
            'tratamientos' => $tratamientos,
            'saldos' => $saldos,
            'abonados' => $abonados,
            'totalCostos' => $tratamientos->sum('costo_acordado'),
            'totalAbonado' => array_sum($abonados),
            'saldoTotal' => array_sum($saldos),
        ]);
    }

    // CU34: MOSTRAR el formulario de registrar un abono.
    public function create(Request $request): View|RedirectResponse
    {
        $tratamiento = TratamientoAplicado::with('tratamiento', 'plan.paciente')
            ->findOrFail($request->input('tratamiento_aplicado_id'));

        $saldo = $this->pagos->saldoPendiente($tratamiento);

        if ($this->estaCancelado($tratamiento) || $saldo <= 0) {
            return redirect()->route('pagos.cuenta', $tratamiento->plan->paciente_id)
                ->withErrors(['operacion' => 'El tratamiento no tiene saldo pendiente para registrar abonos.']);
        }

        $metodos = MetodoPago::where('estado', 'activo')->get();

        return view('pagos.create', [
            'tratamiento' => $tratamiento,
            'saldo' => $saldo,
            'abonado' => $tratamiento->costo_acordado - $saldo,
            'metodos' => $metodos,
        ]);
    }

    // CU34: GUARDAR el abono con su comprobante correlativo.
    public function store(Request $request): RedirectResponse
    {
        // Flujo A1: mensaje junto al campo; conserva los datos.
        $datos = $request->validate($this->reglas(), $this->mensajes());

        $tratamiento = TratamientoAplicado::with('plan')->findOrFail($datos['tratamiento_aplicado_id']);

        // Flujo A2 (RF-76): el monto supera el saldo pendiente.
        if (! $this->pagos->validarMonto($tratamiento, $datos['monto'])) {
            return $this->rechazarMonto($tratamiento, $datos['monto']);
        }

        // Se reintenta (3) si dos abonos simultáneos chocan al generar el comprobante.
        $resultado = DB::transaction(function () use ($datos, $request) {
            $tratamiento = TratamientoAplicado::lockForUpdate()->findOrFail($datos['tratamiento_aplicado_id']);
            $plan = PlanTratamiento::lockForUpdate()->findOrFail($tratamiento->plan_tratamiento_id);
            $tratamiento->setRelation('plan', $plan);

            // Otra petición pudo cancelar el tratamiento o abonar mientras tanto.
            if ($this->estaCancelado($tratamiento)) {
                return ['error' => 'El tratamiento no tiene saldo pendiente para registrar abonos.'];
            }

            if (! $this->pagos->validarMonto($tratamiento, $datos['monto'])) {
                return ['excede' => $tratamiento];
            }

            $pago = Pago::create($datos + [
                'odontologo_id' => $request->user()->id,
                'numero_comprobante' => $this->pagos->generarComprobante(),
                'estado' => 'vigente',
            ]);

            BitacoraService::registrar(BitacoraService::REGISTRAR_ABONO, $request->user()->id, 'pagos', $pago->id);

            return [];
        }, 3);

        if (isset($resultado['error'])) {
            return $this->error($resultado['error']);
        }

        if (isset($resultado['excede'])) {
            return $this->rechazarMonto($resultado['excede'], $datos['monto']);
        }

        return redirect()->route('pagos.cuenta', $tratamiento->plan->paciente_id)->with('exito', 'Abono registrado.');
    }

    // CU35: ANULAR un abono con su motivo; nunca se elimina (RNF-06). El id viene en el formulario.
    public function anular(Request $request): RedirectResponse
    {
        // Flujo A1: motivo vacío.
        $datos = $request->validate([
            'id' => ['required', 'integer', 'exists:pagos,id'],
            'motivo_anulacion' => ['required', 'string', 'max:150'],
        ], $this->mensajes());

        $error = DB::transaction(function () use ($datos, $request) {
            $pago = Pago::lockForUpdate()->findOrFail($datos['id']);

            // Flujo A1: ya anulado.
            if ($pago->estado === 'anulado') {
                return 'El abono ya está anulado.';
            }

            $pago->estado = 'anulado';
            $pago->motivo_anulacion = $datos['motivo_anulacion'];
            $pago->save();

            BitacoraService::registrar(BitacoraService::ANULAR_ABONO, $request->user()->id, 'pagos', $pago->id);

            return null;
        });

        if ($error) {
            return $this->error($error);
        }

        return redirect()->back()->with('exito', 'Abono anulado. El saldo fue recalculado.');
    }

    // Indica si el tratamiento o su plan están cancelados.
    private function estaCancelado(TratamientoAplicado $tratamiento): bool
    {
        return $tratamiento->estado === 'cancelado' || $tratamiento->plan->estado === 'cancelado';
    }

    // Vuelve al formulario con el error del flujo A2; no se registra nada.
    private function rechazarMonto(TratamientoAplicado $tratamiento, $monto): RedirectResponse
    {
        $saldo = $this->pagos->saldoPendiente($tratamiento);

        return redirect()->back()
            ->withErrors(['monto' => "El monto ({$this->enBolivianos($monto)}) supera el saldo pendiente ({$this->enBolivianos($saldo)})"])
            ->withInput();
    }

    // Formato boliviano de los montos: Bs 1.250,00.
    private function enBolivianos($monto): string
    {
        return 'Bs '.number_format((float) $monto, 2, ',', '.');
    }

    // Vuelve atrás con un error de negocio (clave "operacion").
    private function error(string $mensaje): RedirectResponse
    {
        return redirect()->back()->withErrors(['operacion' => $mensaje]);
    }

    // Reglas de los datos del abono. bail: un monto no válido muestra un solo mensaje.
    private function reglas(): array
    {
        return [
            'tratamiento_aplicado_id' => ['required', 'integer', 'exists:tratamientos_aplicados,id'],
            // Límite superior: la columna es decimal(10,2).
            'monto' => ['bail', 'required', 'numeric', 'decimal:0,2', 'gt:0', 'max:99999999.99'],
            'fecha_pago' => ['bail', 'required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'metodo_pago_id' => ['required', 'integer', Rule::exists('metodos_pago', 'id')->where('estado', 'activo')],
            'observaciones' => ['nullable', 'string'],
        ];
    }

    // Mensajes en español.
    private function mensajes(): array
    {
        return [
            'id.required' => 'No se indicó el abono.',
            'id.integer' => 'El abono indicado no es válido.',
            'id.exists' => 'El abono indicado no existe.',
            'motivo_anulacion.required' => 'Debe indicar el motivo de la anulación.',
            'motivo_anulacion.string' => 'El motivo debe ser un texto.',
            'motivo_anulacion.max' => 'El motivo no puede superar los 150 caracteres.',
            'tratamiento_aplicado_id.required' => 'No se indicó el tratamiento.',
            'tratamiento_aplicado_id.integer' => 'El tratamiento indicado no es válido.',
            'tratamiento_aplicado_id.exists' => 'El tratamiento indicado no existe.',
            'monto.required' => 'El campo Monto es obligatorio.',
            'monto.numeric' => 'El monto debe ser un número.',
            'monto.decimal' => 'El monto debe tener como máximo 2 decimales.',
            'monto.gt' => 'El monto debe ser mayor a 0.',
            'monto.max' => 'El monto no puede superar 99999999.99.',
            'fecha_pago.required' => 'El campo Fecha de pago es obligatorio.',
            'fecha_pago.date_format' => 'La fecha de pago no es válida.',
            'fecha_pago.before_or_equal' => 'La fecha de pago no puede ser posterior a hoy.',
            'metodo_pago_id.required' => 'Debe seleccionar el método de pago.',
            'metodo_pago_id.integer' => 'El método de pago indicado no es válido.',
            'metodo_pago_id.exists' => 'El método de pago indicado no existe o está inactivo.',
            'observaciones.string' => 'Las observaciones deben ser un texto.',
        ];
    }
}
