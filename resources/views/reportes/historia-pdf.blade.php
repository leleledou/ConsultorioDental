{{-- RF-84 Historia clínica imprimible: plantilla del PDF, con el encabezado común de reportes/pdf. DejaVu Sans: tildes y ñ correctas. --}}
@php
    $paciente = $datos['paciente'];
    $historia = $datos['historia'];
    $odontograma = $datos['ultimoOdontograma'];
    $cuenta = $datos['cuenta'];
    $bs = fn ($monto) => 'Bs '.number_format((float) $monto, 2, ',', '.');
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $datos['titulo'] }}</title>
    @include('reportes.partials.estilos-pdf')
</head>
<body>
    @include('reportes.partials.encabezado-pdf', ['titulo' => $datos['titulo'], 'detalle' => 'Paciente: '.$paciente->nombreCompleto().' · CI '.$paciente->ci])

    <h3>Datos del paciente</h3>
    <table>
        <tr><th>Fecha de nacimiento</th><td>{{ $paciente->fecha_nacimiento->format('d/m/Y') }} ({{ $paciente->edad() }} años)</td><th>Sexo</th><td>{{ $paciente->sexo === 'F' ? 'Femenino' : 'Masculino' }}</td></tr>
        <tr><th>Teléfono</th><td>{{ $paciente->telefono ?? '—' }}</td><th>Historia N.º</th><td>{{ $historia->id }} (abierta el {{ $historia->fecha_apertura->format('d/m/Y') }})</td></tr>
        <tr><th>Hábitos</th><td colspan="3">{{ $historia->habitos ?? '—' }}</td></tr>
        <tr><th>Observaciones</th><td colspan="3">{{ $historia->observaciones_generales ?? '—' }}</td></tr>
    </table>

    <h3>Antecedentes médicos</h3>
    @if ($historia->antecedentes->isEmpty())
        <p>Sin antecedentes registrados.</p>
    @else
        <table>
            <thead><tr><th>Antecedente</th><th>Observación</th><th>Registro</th></tr></thead>
            <tbody>
                @foreach ($historia->antecedentes as $a)
                    <tr><td>{{ $a->nombre }}</td><td>{{ $a->pivot->observacion ?? '—' }}</td><td>{{ \Carbon\Carbon::parse($a->pivot->fecha_registro)->format('d/m/Y') }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h3>Odontograma</h3>
    @if (! $odontograma)
        <p>El paciente no tiene odontogramas activos.</p>
    @else
        <p>{{ ucfirst($odontograma->tipo) }} del {{ $odontograma->fecha_registro->format('d/m/Y') }} · {{ ucfirst($odontograma->tipo_denticion) }} · {{ $odontograma->odontologo->nombres }} {{ $odontograma->odontologo->apellidos }}</p>
        @include('reportes.partials.odontograma-pdf', ['dibujo' => $datos['dibujo']])
    @endif

    <h3>Planes de tratamiento</h3>
    @if ($datos['planes']->isEmpty())
        <p>Sin planes de tratamiento.</p>
    @else
        <table>
            <thead><tr><th>Fecha</th><th>Odontólogo</th><th>Estado</th><th class="numero">Costo total</th></tr></thead>
            <tbody>
                @foreach ($datos['planes'] as $plan)
                    <tr><td>{{ $plan->fecha_creacion->format('d/m/Y') }}</td><td>{{ $plan->odontologo->nombres }} {{ $plan->odontologo->apellidos }}</td><td>{{ ucfirst($plan->estado) }}</td><td class="numero">{{ $bs($plan->costo_total) }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h3>Tratamientos aplicados y sesiones</h3>
    @if ($datos['tratamientos']->isEmpty())
        <p>Sin tratamientos aplicados.</p>
    @else
        <table>
            <thead><tr><th>Tratamiento</th><th>Pieza</th><th>Diagnóstico</th><th>Inicio</th><th>Estado</th><th>Avance</th><th class="numero">Costo</th></tr></thead>
            <tbody>
                @foreach ($datos['tratamientos'] as $t)
                    <tr>
                        <td>{{ $t->tratamiento->nombre }}</td>
                        <td>{{ $t->piezaDental?->codigo_fdi ?? '—' }}</td>
                        <td>{{ $t->diagnostico->nombre }}</td>
                        <td>{{ $t->fecha_inicio->format('d/m/Y') }}</td>
                        <td>{{ ucfirst($t->estado) }}</td>
                        <td>Sesión {{ $t->sesiones_registradas }} de {{ $t->sesiones_planificadas }}</td>
                        <td class="numero">{{ $bs($t->costo_acordado) }}</td>
                    </tr>
                    @foreach ($datos['sesiones']->get($t->id, collect()) as $s)
                        <tr class="detalle">
                            <td colspan="7">Sesión {{ $s->numero_sesion }} · {{ $s->fecha->format('d/m/Y') }} · {{ $s->odontologo->nombres }} {{ $s->odontologo->apellidos }}{{ $s->avance_tecnico ? ': '.$s->avance_tecnico : '' }}</td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    @endif

    <h3>Estudios diagnósticos</h3>
    @if ($datos['estudios']->isEmpty())
        <p>Sin estudios diagnósticos.</p>
    @else
        <table>
            <thead><tr><th>Fecha</th><th>Tipo de estudio</th><th>Pieza</th><th>Hallazgos</th></tr></thead>
            <tbody>
                @foreach ($datos['estudios'] as $e)
                    <tr><td>{{ $e->fecha_estudio->format('d/m/Y') }}</td><td>{{ $e->tipoEstudio->nombre }}</td><td>{{ $e->pieza?->codigo_fdi ?? '—' }}</td><td>{{ $e->hallazgos ?? '—' }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h3>Valoraciones para implante</h3>
    @if ($datos['valoraciones']->isEmpty())
        <p>Sin valoraciones para implante.</p>
    @else
        <table>
            <thead><tr><th>Fecha</th><th>Pieza</th><th>Hueso</th><th>Injerto</th><th>Resultado</th></tr></thead>
            <tbody>
                @foreach ($datos['valoraciones'] as $v)
                    <tr><td>{{ $v->fecha_valoracion->format('d/m/Y') }}</td><td>{{ $v->pieza->codigo_fdi }} · {{ $v->pieza->nombre }}</td><td>{{ ucfirst($v->estado_hueso) }}</td><td>{{ $v->requiere_injerto ? 'Sí' : 'No' }}</td><td>{{ ucfirst($v->resultado) }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h3>Controles</h3>
    @if ($datos['controles']->isEmpty())
        <p>Sin controles programados.</p>
    @else
        <table>
            <thead><tr><th>Fecha prevista</th><th>Tratamiento</th><th>Motivo</th><th>Estado</th></tr></thead>
            <tbody>
                @foreach ($datos['controles'] as $control)
                    <tr><td>{{ $control->fecha_prevista->format('d/m/Y') }}</td><td>{{ $control->tratamientoAplicado->tratamiento->nombre }}</td><td>{{ $control->motivo ?? '—' }}</td><td>{{ ucfirst($control->estado) }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h3>Estado de cuenta</h3>
    <table>
        <tr><th>Costo total</th><td class="numero">{{ $bs($cuenta['costo_total']) }}</td></tr>
        <tr><th>Total abonado</th><td class="numero">{{ $bs($cuenta['abonado']) }}</td></tr>
        <tr><th>Saldo pendiente</th><td class="numero"><strong>{{ $bs($cuenta['saldo']) }}</strong></td></tr>
    </table>
</body>
</html>
