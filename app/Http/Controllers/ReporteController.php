<?php

namespace App\Http\Controllers;

use App\Models\Especialidad;
use App\Models\EstadoCita;
use App\Models\MetodoPago;
use App\Models\Paciente;
use App\Models\Tratamiento;
use App\Models\User;
use App\Services\ReporteService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CU42 Generar reportes clínicos, CU43 Generar reportes de agenda, CU44 Generar reportes
 * financieros y CU45 Exportar reporte. Para los dos roles. Las consultas y los archivos
 * están en ReporteService. Los reportes solo leen datos: no se registran en la bitácora.
 */
class ReporteController extends Controller
{
    public function __construct(private ReporteService $reportes)
    {
    }

    // CU42: MOSTRAR los reportes clínicos y generar el elegido.
    public function clinico(Request $request): View
    {
        return $this->pantalla($request, 'clinico', 'reportes.clinicos', [
            'odontologos' => $this->odontologos(),
            'especialidades' => Especialidad::orderBy('nombre')->get(),
            'tratamientos' => Tratamiento::orderBy('nombre')->get(),
            'estados' => ['planificado', 'en proceso', 'concluido', 'cancelado'],
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

    // CU45: EXPORTAR a PDF o Excel la misma consulta del reporte en pantalla.
    public function exportar(Request $request): Response
    {
        $familia = $request->validate(
            ['reporte' => ['required', Rule::in(array_keys(ReporteService::ESTATICOS))]],
            ['reporte.required' => 'No se indicó el reporte.', 'reporte.in' => 'El reporte indicado no es válido.']
        );

        $reporte = $familia['reporte'];

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
            'formato.required' => 'El formato debe ser pdf o excel.',
            'formato.in' => 'El formato debe ser pdf o excel.',
        ];
    }
}
