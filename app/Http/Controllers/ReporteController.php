<?php

namespace App\Http\Controllers;

use App\Models\Especialidad;
use App\Models\EstadoCita;
use App\Models\HistoriaClinica;
use App\Models\MetodoPago;
use App\Models\Paciente;
use App\Models\Tratamiento;
use App\Models\User;
use App\Services\ReporteService;
use App\Services\ReporteVozService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * CU42 Generar reportes clínicos, CU43 Generar reportes de agenda, CU44 Generar reportes
 * financieros y CU45 Exportar reporte. Para los dos roles. Las consultas y los archivos
 * están en ReporteService. Los reportes solo leen datos: no se registran en la bitácora.
 *
 * Ciclo 4: RF-84 historia clínica imprimible y RF-85 comparativo de odontogramas, documentos
 * por paciente que se ven en pantalla y se exportan por CU45 (solo a PDF).
 */
class ReporteController extends Controller
{
    public function __construct(
        private ReporteService $reportes,
        private ReporteVozService $voz,
    ) {}

    // CU42: MOSTRAR los reportes clínicos y generar el elegido.
    public function clinico(Request $request): View
    {
        return $this->pantalla($request, 'clinico', 'reportes.clinicos', [
            'odontologos' => $this->odontologos(),
            'especialidades' => Especialidad::orderBy('nombre')->get(),
            'tratamientos' => Tratamiento::orderBy('nombre')->get(),
            'estados' => ['planificado', 'en proceso', 'concluido', 'cancelado'],
            // RF-84 y RF-85: buscador de paciente (misma búsqueda de CU09), hasta 10 resultados.
            'pacientesDocumento' => $request->filled('buscar_paciente')
                ? Paciente::buscar($request->input('buscar_paciente'))->orderBy('apellidos')->orderBy('nombres')->limit(10)->get()
                : collect(),
        ]);
    }

    // CU43: MOSTRAR los reportes de agenda y generar el elegido.
    public function agenda(Request $request): View
    {
        return $this->pantalla($request, 'agenda', 'reportes.agenda', [
            'odontologos' => $this->odontologos(),
            'pacientes' => Paciente::orderBy('apellidos')->orderBy('nombres')->get(),
            'estados' => EstadoCita::orderBy('id')->get(),
        ]);
    }

    // CU44: MOSTRAR los reportes financieros y generar el elegido.
    public function financiero(Request $request): View
    {
        return $this->pantalla($request, 'financiero', 'reportes.financieros', [
            'odontologos' => $this->odontologos(),
            'metodos' => MetodoPago::orderBy('nombre')->get(),
            'estados' => ['vigente', 'anulado'],
        ]);
    }

    // Reportes por voz (entrada alternativa de CU42 a CU44): MOSTRAR la pantalla y, si llegan
    // tipo y periodo, generar el reporte estático con el mismo método de las otras pantallas.
    public function voz(Request $request): View
    {
        $reporte = null;
        $resultado = null;

        if ($request->filled(['tipo', 'fecha_inicio', 'fecha_fin'])) {
            // Siempre es un reporte estático; modo queda en la consulta para que CU45 exporte lo mismo.
            $request->merge(['modo' => 'estatico']);
            // Familia del tipo; si el tipo no existe, la validación lo rechaza con «El reporte indicado no es válido.».
            $reporte = $this->familia((string) $request->input('tipo')) ?? 'clinico';
            $datos = $request->validate($this->reglas($reporte), $this->mensajes());
            $resultado = $this->generar($datos, $reporte);
        }

        return view('reportes.voz', [
            'reporte' => $reporte,
            'resultado' => $resultado,
            'interpretacion' => session('interpretacion'),
            'frase' => session('frase', old('frase', '')),
        ]);
    }

    // Reportes por voz: INTERPRETAR la frase (Gemini o reglas) y abrir el reporte en la pantalla.
    public function interpretarVoz(Request $request): RedirectResponse
    {
        $datos = $request->validate(['frase' => ['required', 'string', 'max:200']], $this->mensajes());

        $consulta = $this->voz->interpretar($datos['frase']);

        return redirect()->route('reportes.voz', [
            'tipo' => $consulta['tipo'],
            'fecha_inicio' => $consulta['fecha_inicio'],
            'fecha_fin' => $consulta['fecha_fin'],
        ])->with([
            'interpretacion' => $consulta['interpretacion'],
            'frase' => $datos['frase'],
        ]);
    }

    // Familia (clinico, agenda o financiero) de un reporte estático, o null si no existe.
    private function familia(string $tipo): ?string
    {
        foreach (ReporteService::ESTATICOS as $familia => $tipos) {
            if (isset($tipos[$tipo])) {
                return $familia;
            }
        }

        return null;
    }

    // RF-84: MOSTRAR la historia clínica imprimible de UN paciente.
    public function historiaClinica($paciente): View|RedirectResponse
    {
        $paciente = Paciente::findOrFail($paciente);
        $datos = $this->reportes->historiaClinica($paciente->id);

        if ($datos === null) {
            return redirect()->route('pacientes.mostrar', $paciente->id)
                ->withErrors(['operacion' => 'El paciente aún no tiene historia clínica registrada.']);
        }

        return view('reportes.historia', ['datos' => $datos]);
    }

    // RF-85: MOSTRAR el comparativo de dos odontogramas (GET a y b; sin ellos, el inicial y el último de evolución).
    public function comparativoOdontograma(Request $request, $paciente): View|RedirectResponse
    {
        $paciente = Paciente::findOrFail($paciente);
        $par = $request->validate($this->reglasComparativo($paciente->id), $this->mensajes());

        // Hacen falta los dos odontogramas, y distintos (mismo mensaje que CU26 flujo A1).
        if ($this->parInvalido($par)) {
            return redirect()->route('reportes.odontogramas', $paciente->id)
                ->withErrors(['operacion' => 'Seleccione dos odontogramas diferentes para comparar.']);
        }

        $datos = $this->reportes->comparativoOdontograma($paciente->id, $par['a'] ?? null, $par['b'] ?? null);

        return view('reportes.odontogramas', ['datos' => $datos]);
    }

    // CU45: EXPORTAR a PDF o Excel la misma consulta del reporte en pantalla.
    // También exporta los documentos por paciente de RF-84 y RF-85, solo a PDF.
    public function exportar(Request $request): Response
    {
        $familia = $request->validate(
            ['reporte' => ['required', Rule::in([...array_keys(ReporteService::ESTATICOS), ...array_keys(ReporteService::DOCUMENTOS)])]],
            ['reporte.required' => 'No se indicó el reporte.', 'reporte.in' => 'El reporte indicado no es válido.']
        );

        $reporte = $familia['reporte'];

        if (isset(ReporteService::DOCUMENTOS[$reporte])) {
            return $this->exportarDocumento($request, $reporte);
        }

        $datos = $request->validate($this->reglas($reporte) + [
            'formato' => ['required', Rule::in(['pdf', 'excel'])],
        ], $this->mensajes());

        $resultado = $this->generar($datos, $reporte);
        $archivo = $resultado['nombre_archivo'].'_'.today()->format('Y-m-d');

        if ($datos['formato'] === 'pdf') {
            return response($this->reportes->exportarPdf($resultado), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "attachment; filename=\"{$archivo}.pdf\"",
            ]);
        }

        return response($this->reportes->exportarExcel($resultado), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$archivo}.xlsx\"",
        ]);
    }

    // CU45 para RF-84 y RF-85: misma validación de formato, pero un documento clínico solo va a PDF.
    private function exportarDocumento(Request $request, string $reporte): Response
    {
        $datos = $request->validate([
            'paciente' => ['required', 'integer', 'exists:pacientes,id'],
            'formato' => ['required', Rule::in(['pdf', 'excel'])],
        ], $this->mensajes());

        if ($datos['formato'] !== 'pdf') {
            throw ValidationException::withMessages(['formato' => 'Este documento solo se puede exportar a PDF.']);
        }

        if ($reporte === 'historia') {
            $documento = $this->reportes->historiaClinica($datos['paciente']);

            if ($documento === null) {
                throw ValidationException::withMessages(['operacion' => 'El paciente aún no tiene historia clínica registrada.']);
            }

            $contenido = $this->reportes->exportarHistoriaPdf($documento);
        } else {
            $par = $request->validate($this->reglasComparativo($datos['paciente']), $this->mensajes());

            if ($this->parInvalido($par)) {
                throw ValidationException::withMessages(['operacion' => 'Seleccione dos odontogramas diferentes para comparar.']);
            }

            $documento = $this->reportes->comparativoOdontograma($datos['paciente'], $par['a'] ?? null, $par['b'] ?? null);

            if ($documento['aviso']) {
                throw ValidationException::withMessages(['operacion' => $documento['aviso']]);
            }

            $contenido = $this->reportes->exportarComparativoPdf($documento);
        }

        $archivo = $documento['nombre_archivo'].'_'.today()->format('Y-m-d');

        return response($contenido, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$archivo}.pdf\"",
        ]);
    }

    // RF-85: los odontogramas a y b son opcionales, pero deben ser de la historia del paciente.
    private function reglasComparativo(int $pacienteId): array
    {
        $delPaciente = Rule::exists('odontogramas', 'id')->where(fn ($q) => $q->whereIn(
            'historia_clinica_id',
            HistoriaClinica::where('paciente_id', $pacienteId)->select('id')
        ));

        return [
            'a' => ['nullable', 'integer', $delPaciente],
            'b' => ['nullable', 'integer', $delPaciente],
        ];
    }

    // Se indicó solo uno de los dos odontogramas, o el mismo dos veces.
    private function parInvalido(array $par): bool
    {
        $a = $par['a'] ?? null;
        $b = $par['b'] ?? null;

        return ($a === null) !== ($b === null) || ($a !== null && (int) $a === (int) $b);
    }

    // Secuencia común de CU42 a CU44: sin parámetros abre la pantalla; con modo genera el reporte.
    private function pantalla(Request $request, string $reporte, string $vista, array $catalogos): View
    {
        $resultado = null;

        if ($request->filled('modo')) {
            // Flujo A1: mensajes de las fechas y de las columnas.
            $datos = $request->validate($this->reglas($reporte), $this->mensajes());
            $resultado = $this->generar($datos, $reporte);
        }

        return view($vista, $catalogos + [
            'reporte' => $reporte,
            'estaticos' => ReporteService::ESTATICOS[$reporte],
            'columnas' => ReporteService::columnasDisponibles($reporte),
            'resultado' => $resultado,
        ]);
    }

    // Ejecuta el reporte estático o dinámico; la pantalla y la exportación usan este mismo método.
    private function generar(array $datos, string $reporte): array
    {
        if ($datos['modo'] === 'estatico') {
            return $this->reportes->estatico($datos['tipo'], [
                'fecha_inicio' => $datos['fecha_inicio'],
                'fecha_fin' => $datos['fecha_fin'],
            ]);
        }

        return $this->reportes->dinamico(['reporte' => $reporte] + $datos, $datos['columnas']);
    }

    // Odontólogos activos, con el mismo criterio de CitaController.
    private function odontologos()
    {
        return User::where('estado', 'activo')->orderBy('apellidos')->orderBy('nombres')->get();
    }

    // Reglas del reporte de una familia. Las columnas solo salen de la lista blanca.
    private function reglas(string $reporte): array
    {
        return [
            'modo' => ['required', Rule::in(['estatico', 'dinamico'])],
            'tipo' => ['exclude_unless:modo,estatico', 'required', Rule::in(array_keys(ReporteService::ESTATICOS[$reporte]))],
            'fecha_inicio' => ['bail', 'required', 'date_format:Y-m-d', 'before_or_equal:fecha_fin'],
            'fecha_fin' => ['required', 'date_format:Y-m-d'],
            'columnas' => ['exclude_unless:modo,dinamico', 'required', 'array', 'min:1'],
            'columnas.*' => ['exclude_unless:modo,dinamico', Rule::in(array_keys(ReporteService::columnasDisponibles($reporte)))],
        ] + $this->reglasFiltros($reporte);
    }

    // Filtros opcionales del reporte dinámico de cada familia.
    private function reglasFiltros(string $reporte): array
    {
        $odontologo = ['exclude_unless:modo,dinamico', 'nullable', 'integer', 'exists:users,id'];

        return match ($reporte) {
            'clinico' => [
                'odontologo_id' => $odontologo,
                'especialidad_id' => ['exclude_unless:modo,dinamico', 'nullable', 'integer', 'exists:especialidades,id'],
                'tratamiento_id' => ['exclude_unless:modo,dinamico', 'nullable', 'integer', 'exists:tratamientos,id'],
                'estado' => ['exclude_unless:modo,dinamico', 'nullable', Rule::in(['planificado', 'en proceso', 'concluido', 'cancelado'])],
            ],
            'agenda' => [
                'odontologo_id' => $odontologo,
                'paciente_id' => ['exclude_unless:modo,dinamico', 'nullable', 'integer', 'exists:pacientes,id'],
                'estado_cita_id' => ['exclude_unless:modo,dinamico', 'nullable', 'integer', 'exists:estados_cita,id'],
            ],
            'financiero' => [
                'odontologo_id' => $odontologo,
                'metodo_pago_id' => ['exclude_unless:modo,dinamico', 'nullable', 'integer', 'exists:metodos_pago,id'],
                'estado' => ['exclude_unless:modo,dinamico', 'nullable', Rule::in(['vigente', 'anulado'])],
            ],
        };
    }

    // Mensajes en español.
    private function mensajes(): array
    {
        return [
            'modo.required' => 'No se indicó el modo del reporte.',
            'modo.in' => 'El modo del reporte no es válido.',
            'tipo.required' => 'Debe seleccionar el reporte.',
            'tipo.in' => 'El reporte indicado no es válido.',
            'fecha_inicio.required' => 'El campo Fecha inicial es obligatorio.',
            'fecha_inicio.date_format' => 'La fecha inicial no es válida.',
            'fecha_inicio.before_or_equal' => 'La fecha inicial no puede ser posterior a la fecha final.',
            'fecha_fin.required' => 'El campo Fecha final es obligatorio.',
            'fecha_fin.date_format' => 'La fecha final no es válida.',
            'columnas.required' => 'Debe seleccionar al menos una columna.',
            'columnas.array' => 'Debe seleccionar al menos una columna.',
            'columnas.min' => 'Debe seleccionar al menos una columna.',
            'columnas.*.in' => 'La columna elegida no es válida.',
            'odontologo_id.integer' => 'El odontólogo indicado no es válido.',
            'odontologo_id.exists' => 'El odontólogo indicado no existe.',
            'especialidad_id.integer' => 'La especialidad indicada no es válida.',
            'especialidad_id.exists' => 'La especialidad indicada no existe.',
            'tratamiento_id.integer' => 'El tratamiento indicado no es válido.',
            'tratamiento_id.exists' => 'El tratamiento indicado no existe.',
            'paciente_id.integer' => 'El paciente indicado no es válido.',
            'paciente_id.exists' => 'El paciente indicado no existe.',
            'estado_cita_id.integer' => 'El estado indicado no es válido.',
            'estado_cita_id.exists' => 'El estado indicado no existe.',
            'metodo_pago_id.integer' => 'El método de pago indicado no es válido.',
            'metodo_pago_id.exists' => 'El método de pago indicado no existe.',
            'estado.in' => 'El estado indicado no es válido.',
            'paciente.required' => 'No se indicó el paciente.',
            'paciente.integer' => 'El paciente indicado no es válido.',
            'paciente.exists' => 'El paciente indicado no existe.',
            'a.integer' => 'El odontograma A indicado no es válido.',
            'a.exists' => 'El odontograma A no pertenece al paciente.',
            'b.integer' => 'El odontograma B indicado no es válido.',
            'b.exists' => 'El odontograma B no pertenece al paciente.',
            'formato.required' => 'El formato debe ser pdf o excel.',
            'formato.in' => 'El formato debe ser pdf o excel.',
            'frase.required' => 'Diga o escriba la consulta del reporte.',
            'frase.string' => 'La consulta no es válida.',
            'frase.max' => 'La consulta no puede superar los 200 caracteres.',
        ];
    }
}
