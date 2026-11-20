<?php

namespace App\Http\Controllers;

use App\Models\EstudioDiagnostico;
use App\Models\Paciente;
use App\Models\PiezaDental;
use App\Models\TipoEstudio;
use App\Models\TratamientoAplicado;
use App\Services\BitacoraService;
use App\Services\EstudioDiagnosticoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * CU32 Gestionar estudios diagnósticos (RF-52 a RF-57, RF-59).
 * Para los dos roles (el administrador también es odontólogo).
 * Cada estudio guarda un archivo (PDF, JPG o PNG de hasta 10 MB, en el disco public) o un
 * enlace al centro radiológico, en la misma columna ruta_archivo (EstudioDiagnosticoService).
 * El archivo se escribe antes de la transacción y se borra si la transacción falla; el archivo
 * reemplazado o eliminado se borra recién cuando la transacción terminó bien.
 */
class EstudioDiagnosticoController extends Controller
{
    public function __construct(private EstudioDiagnosticoService $estudios)
    {
    }

    // MOSTRAR los estudios de UN paciente, del más reciente al más antiguo, con filtro por tipo.
    public function index(Request $request, $paciente): View
    {
        $paciente = Paciente::findOrFail($paciente);

        $request->validate(
            [
                'tipo_estudio_id' => ['nullable', 'integer'],
                'tratamiento_aplicado_id' => ['nullable', 'integer'],
            ],
            [
                'tipo_estudio_id.integer' => 'El tipo de estudio del filtro no es válido.',
                'tratamiento_aplicado_id.integer' => 'El tratamiento aplicado indicado no es válido.',
            ]
        );

        $tipoFiltro = $request->input('tipo_estudio_id');

        $estudios = EstudioDiagnostico::with('tipoEstudio', 'odontologo', 'pieza', 'tratamientoAplicado.tratamiento')
            ->where('paciente_id', $paciente->id)
            ->when($tipoFiltro, fn ($q, $tipo) => $q->where('tipo_estudio_id', $tipo))
            ->orderByDesc('fecha_estudio')
            ->orderByDesc('id')
            ->get();

        $formulario = $this->datosFormulario($paciente);

        // RF-59: desde la ficha del tratamiento se llega con el tratamiento (y el tipo) ya elegidos.
        $preseleccion = [
            'tratamiento_aplicado_id' => $request->input('tratamiento_aplicado_id'),
            'tipo_estudio_id' => $request->input('tipo'),
            'abrir' => $request->boolean('nuevo'),
        ];

        return view('estudios.index', compact('paciente', 'estudios', 'tipoFiltro', 'preseleccion') + $formulario);
    }

    // CREAR un estudio: odontólogo de la sesión, fecha de registro de hoy, archivo o enlace.
    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate($this->reglas(true), $this->mensajes());

        if ($error = $this->tratamientoAjeno($datos['tratamiento_aplicado_id'] ?? null, (int) $datos['paciente_id'])) {
            return $error;
        }

        $ruta = $request->hasFile('archivo') ? $this->estudios->guardarArchivo($request->file('archivo')) : ($datos['enlace'] ?? null);

        try {
            $estudio = DB::transaction(function () use ($datos, $ruta, $request) {
                $estudio = EstudioDiagnostico::create($this->columnas($datos) + [
                    'paciente_id' => $datos['paciente_id'],
                    'odontologo_id' => $request->user()->id,
                    'ruta_archivo' => $ruta,
                    'fecha_registro' => today(),
                ]);

                BitacoraService::registrar(BitacoraService::REGISTRAR_ESTUDIO, $request->user()->id, 'estudios_diagnosticos', $estudio->id);

                return $estudio;
            });
        } catch (Throwable $e) {
            // No deja archivos huérfanos si la base rechazó el registro.
            $this->estudios->borrarArchivo($ruta);
            throw $e;
        }

        return redirect()->route('estudios.mostrar', $estudio->id)
            ->with('exito', 'Estudio diagnóstico registrado correctamente.');
    }

    // MODIFICAR un estudio. El id viene en el formulario. Sin archivo ni enlace nuevos, conserva el actual.
    public function update(Request $request): RedirectResponse
    {
        $request->validate($this->reglaId(), $this->mensajes());

        $estudio = EstudioDiagnostico::findOrFail($request->input('id'));

        $datos = $request->validate($this->reglas(false), $this->mensajes());

        if ($error = $this->tratamientoAjeno($datos['tratamiento_aplicado_id'] ?? null, (int) $estudio->paciente_id)) {
            return $error;
        }

        $anterior = $estudio->ruta_archivo;
        $nueva = match (true) {
            $request->hasFile('archivo') => $this->estudios->guardarArchivo($request->file('archivo')),
            ! empty($datos['enlace']) => $datos['enlace'],
            default => $anterior,
        };

        try {
            DB::transaction(function () use ($estudio, $datos, $nueva, $request) {
                $estudio->update($this->columnas($datos) + ['ruta_archivo' => $nueva]);

                BitacoraService::registrar(BitacoraService::MODIFICAR_ESTUDIO, $request->user()->id, 'estudios_diagnosticos', $estudio->id);
            });
        } catch (Throwable $e) {
            if ($nueva !== $anterior) {
                $this->estudios->borrarArchivo($nueva);
            }
            throw $e;
        }

        // El archivo reemplazado se borra del disco (un enlace no se toca).
        if ($nueva !== $anterior) {
            $this->estudios->borrarArchivo($anterior);
        }

        return redirect()->route('estudios.mostrar', $estudio->id)
            ->with('exito', 'Estudio diagnóstico actualizado correctamente.');
    }

    // ELIMINAR un estudio y su archivo. El id viene en el formulario.
    public function delete(Request $request): RedirectResponse
    {
        $request->validate($this->reglaId(), $this->mensajes());

        $estudio = EstudioDiagnostico::withCount('valoraciones')->findOrFail($request->input('id'));

        // La FK de valoraciones_implante es restrictiva: el estudio que respalda una valoración se conserva.
        if ($estudio->valoraciones_count > 0) {
            return redirect()->back()->withErrors(['operacion' => 'No se puede eliminar: el estudio respalda una valoración para implante.']);
        }

        DB::transaction(function () use ($estudio, $request) {
            $estudio->delete();

            BitacoraService::registrar(BitacoraService::ELIMINAR_ESTUDIO, $request->user()->id, 'estudios_diagnosticos', $estudio->id);
        });

        $this->estudios->borrarArchivo($estudio->ruta_archivo);

        return redirect()->route('estudios.listar', $estudio->paciente_id)
            ->with('exito', 'Estudio diagnóstico eliminado.');
    }

    // MOSTRAR UN estudio: vista previa si es imagen, descarga si es PDF, enlace externo si es URL.
    public function show($id): View
    {
        $estudio = EstudioDiagnostico::with('paciente', 'tipoEstudio', 'odontologo', 'pieza', 'tratamientoAplicado.tratamiento')
            ->withCount('valoraciones')
            ->findOrFail($id);

        $esEnlace = $this->estudios->esEnlace($estudio->ruta_archivo);
        $extension = $esEnlace ? null : Str::lower(pathinfo((string) $estudio->ruta_archivo, PATHINFO_EXTENSION));

        return view('estudios.show', compact('estudio', 'esEnlace', 'extension') + $this->datosFormulario($estudio->paciente));
    }

    // DESCARGAR el archivo del estudio; si es un enlace, lleva a la dirección externa.
    public function descargar($id): RedirectResponse|StreamedResponse
    {
        $estudio = EstudioDiagnostico::with('tipoEstudio')->findOrFail($id);
        $ruta = $estudio->ruta_archivo;

        if ($this->estudios->esEnlace($ruta)) {
            return redirect()->away($ruta);
        }

        $disco = Storage::disk(EstudioDiagnosticoService::DISCO);

        abort_unless($ruta && $disco->exists($ruta), 404);

        $nombre = 'estudio-'.$estudio->id.'-'.Str::slug($estudio->tipoEstudio->nombre).'.'.pathinfo($ruta, PATHINFO_EXTENSION);

        return $disco->download($ruta, $nombre);
    }

    // Catálogos del formulario: tipos activos, piezas y tratamientos del paciente.
    private function datosFormulario(Paciente $paciente): array
    {
        return [
            'tipos' => TipoEstudio::where('estado', 'activo')->orderBy('nombre')->get(),
            'piezas' => PiezaDental::orderBy('id')->get(),
            'tratamientos' => TratamientoAplicado::with('tratamiento')
                ->whereHas('plan', fn ($q) => $q->where('paciente_id', $paciente->id))
                ->orderByDesc('fecha_inicio')
                ->get(),
        ];
    }

    // Si se indicó un tratamiento aplicado, tiene que ser del mismo paciente.
    private function tratamientoAjeno($tratamientoId, int $pacienteId): ?RedirectResponse
    {
        if (empty($tratamientoId)) {
            return null;
        }

        $delPaciente = TratamientoAplicado::where('id', $tratamientoId)
            ->whereHas('plan', fn ($q) => $q->where('paciente_id', $pacienteId))
            ->exists();

        return $delPaciente ? null : redirect()->back()->withInput()
            ->withErrors(['tratamiento_aplicado_id' => 'El tratamiento aplicado no pertenece a este paciente.']);
    }

    // Columnas editables del estudio (sin paciente, odontólogo, ruta ni fecha de registro).
    private function columnas(array $datos): array
    {
        return [
            'tipo_estudio_id' => $datos['tipo_estudio_id'],
            'pieza_dental_id' => $datos['pieza_dental_id'] ?? null,
            'tratamiento_aplicado_id' => $datos['tratamiento_aplicado_id'] ?? null,
            'fecha_estudio' => $datos['fecha_estudio'],
            'centro_radiologico' => $datos['centro_radiologico'] ?? null,
            'hallazgos' => $datos['hallazgos'] ?? null,
        ];
    }

    // Regla del id que llega oculto en el formulario.
    private function reglaId(): array
    {
        return ['id' => ['required', 'integer', 'exists:estudios_diagnosticos,id']];
    }

    // Reglas de los datos. Al registrar, el archivo o el enlace es obligatorio (uno solo);
    // al modificar, ninguno de los dos lo es (se conserva el actual).
    private function reglas(bool $alta): array
    {
        $archivo = ['bail', 'nullable'];
        if ($alta) {
            $archivo[] = 'required_without:enlace';
        }

        return [
            'paciente_id' => $alta ? ['required', 'integer', 'exists:pacientes,id'] : ['prohibited'],
            'tipo_estudio_id' => ['required', 'integer', Rule::exists('tipos_estudio', 'id')->where('estado', 'activo')],
            'fecha_estudio' => ['bail', 'required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'pieza_dental_id' => ['nullable', 'integer', 'exists:piezas_dentales,id'],
            'tratamiento_aplicado_id' => ['nullable', 'integer', 'exists:tratamientos_aplicados,id'],
            'centro_radiologico' => ['nullable', 'string', 'max:80'],
            'hallazgos' => ['nullable', 'string'],
            'archivo' => array_merge($archivo, ['prohibits:enlace', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240']),
            'enlace' => ['bail', 'nullable', 'string', 'max:255', 'url:http,https'],
        ];
    }

    // Mensajes en español.
    private function mensajes(): array
    {
        return [
            'id.required' => 'No se indicó el estudio.',
            'id.integer' => 'El estudio indicado no es válido.',
            'id.exists' => 'El estudio indicado no existe.',
            'paciente_id.required' => 'No se indicó el paciente.',
            'paciente_id.integer' => 'El paciente indicado no es válido.',
            'paciente_id.exists' => 'El paciente indicado no existe.',
            'paciente_id.prohibited' => 'El paciente de un estudio no se puede cambiar.',
            'tipo_estudio_id.required' => 'Seleccione el tipo de estudio.',
            'tipo_estudio_id.integer' => 'El tipo de estudio indicado no es válido.',
            'tipo_estudio_id.exists' => 'El tipo de estudio indicado no existe o está dado de baja.',
            'fecha_estudio.required' => 'El campo Fecha del estudio es obligatorio.',
            'fecha_estudio.date_format' => 'La fecha debe tener el formato AAAA-MM-DD.',
            'fecha_estudio.before_or_equal' => 'La fecha del estudio no puede ser futura.',
            'pieza_dental_id.integer' => 'La pieza dental indicada no es válida.',
            'pieza_dental_id.exists' => 'La pieza dental indicada no existe.',
            'tratamiento_aplicado_id.integer' => 'El tratamiento aplicado indicado no es válido.',
            'tratamiento_aplicado_id.exists' => 'El tratamiento aplicado indicado no existe.',
            'centro_radiologico.string' => 'El centro radiológico debe ser un texto.',
            'centro_radiologico.max' => 'El centro radiológico no puede superar los 80 caracteres.',
            'hallazgos.string' => 'Los hallazgos deben ser un texto.',
            'archivo.required_without' => 'Debe adjuntar un archivo o registrar un enlace.',
            'archivo.prohibits' => 'Registre el archivo o el enlace, no ambos.',
            'archivo.file' => 'El archivo adjunto no es válido.',
            'archivo.mimes' => 'Formato no admitido. Use PDF, JPG o PNG.',
            'archivo.max' => 'El archivo supera los 10 MB. Registre el estudio mediante un enlace.',
            // Un archivo mayor que upload_max_filesize de PHP llega como subida fallida.
            'archivo.uploaded' => 'El archivo supera los 10 MB. Registre el estudio mediante un enlace.',
            'enlace.string' => 'El enlace debe ser una dirección web válida.',
            'enlace.max' => 'El enlace no puede superar los 255 caracteres.',
            'enlace.url' => 'El enlace debe ser una dirección web válida.',
        ];
    }
}
