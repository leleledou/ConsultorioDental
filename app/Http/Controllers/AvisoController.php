<?php

namespace App\Http\Controllers;

use App\Models\Aviso;
use App\Models\Control;
use App\Models\TratamientoAplicado;
use App\Services\AvisoService;
use App\Services\BitacoraService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CU41 Consultar avisos (RF-82) y el disparo manual de CU40 Generar avisos internos
 * (flujo alternativo del Temporizador, ver App\Console\Commands\GenerarAvisos).
 * Cada odontólogo ve solo sus avisos (el administrador también es odontólogo).
 * La tabla no tiene fecha de lectura: marcar como leído solo cambia `leido`.
 */
class AvisoController extends Controller
{
    public function __construct(private AvisoService $avisos)
    {
    }

    // MOSTRAR los avisos del usuario: los no leídos primero y, dentro de cada grupo, los más recientes.
    public function index(Request $request): View
    {
        $request->validate(
            ['tipo' => ['nullable', Rule::in(Aviso::TIPOS)]],
            ['tipo.in' => 'El tipo de aviso del filtro no es válido.']
        );

        $usuarioId = $request->user()->id;
        $tipo = $request->input('tipo');

        $avisos = Aviso::where('odontologo_id', $usuarioId)
            ->when($tipo, fn ($q, $t) => $q->where('tipo', $t))
            ->orderBy('leido')
            ->orderByDesc('fecha_generacion')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        // Contadores por tipo: total y sin leer.
        $contadores = Aviso::where('odontologo_id', $usuarioId)
            ->selectRaw('tipo, count(*) as total, sum(case when leido = 0 then 1 else 0 end) as sin_leer')
            ->groupBy('tipo')
            ->get()
            ->keyBy('tipo');

        $enlaces = $this->enlaces($avisos->getCollection());

        return view('avisos.index', compact('avisos', 'contadores', 'tipo', 'enlaces'));
    }

    // MARCAR COMO LEÍDO un aviso propio, o todos si llega el campo "todos".
    public function leer(Request $request): RedirectResponse
    {
        $usuarioId = $request->user()->id;

        if ($request->boolean('todos')) {
            $cantidad = Aviso::where('odontologo_id', $usuarioId)->where('leido', false)->update(['leido' => true]);

            return redirect()->back()->with('exito', "Se marcaron {$cantidad} avisos como leídos.");
        }

        // Solo los avisos del propio usuario.
        $request->validate(
            ['id' => ['required', 'integer', Rule::exists('avisos', 'id')->where('odontologo_id', $usuarioId)]],
            [
                'id.required' => 'No se indicó el aviso.',
                'id.integer' => 'El aviso indicado no es válido.',
                'id.exists' => 'El aviso indicado no existe.',
            ]
        );

        Aviso::whereKey($request->input('id'))->update(['leido' => true]);

        return redirect()->back()->with('exito', 'Aviso marcado como leído.');
    }

    // CU40, flujo alternativo: el usuario dispara a mano lo que hace el Temporizador cada día.
    public function generar(Request $request): RedirectResponse
    {
        $total = DB::transaction(function () use ($request) {
            $total = array_sum($this->avisos->generar());

            BitacoraService::registrar(BitacoraService::GENERAR_AVISOS, $request->user()->id, 'avisos', null);

            return $total;
        });

        return redirect()->back()->with('exito', $total > 0 ? "Se generaron {$total} avisos." : 'No hay avisos nuevos que generar.');
    }

    /**
     * Enlace al origen de cada aviso según su tipo: la cita, el control (en la ficha de su
     * tratamiento), el estado de cuenta o la ficha del tratamiento aplicado. Carga los
     * orígenes en lote; si el origen ya no existe, el aviso queda sin enlace.
     *
     * @return array<int, string> id del aviso => URL
     */
    private function enlaces(Collection $avisos): array
    {
        $referencias = fn (array $tipos) => $avisos->whereIn('tipo', $tipos)->pluck('referencia_id')->filter()->unique();

        $controles = Control::whereIn('id', $referencias([Aviso::CONTROL_PENDIENTE]))->pluck('tratamiento_aplicado_id', 'id');
        $tratamientos = TratamientoAplicado::with('plan:id,paciente_id')
            ->whereIn('id', $referencias([Aviso::SALDO_PENDIENTE, Aviso::ESTUDIO_PENDIENTE, Aviso::SESION_SIN_CITA]))
            ->get(['id', 'plan_tratamiento_id'])
            ->keyBy('id');

        $enlaces = [];
        foreach ($avisos as $aviso) {
            $ref = $aviso->referencia_id;

            $url = match ($aviso->tipo) {
                Aviso::CITA_PROXIMA => $ref ? route('citas.mostrar', $ref) : null,
                Aviso::CONTROL_PENDIENTE => isset($controles[$ref]) ? route('tratamientos-aplicados.mostrar', $controles[$ref]).'#controles' : null,
                Aviso::SALDO_PENDIENTE => isset($tratamientos[$ref]) ? route('pagos.cuenta', $tratamientos[$ref]->plan->paciente_id) : null,
                default => isset($tratamientos[$ref]) ? route('tratamientos-aplicados.mostrar', $ref) : null,
            };

            if ($url) {
                $enlaces[$aviso->id] = $url;
            }
        }

        return $enlaces;
    }
}
