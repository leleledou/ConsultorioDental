{{-- CU25 Registrar odontograma: odontograma gráfico (RF-23) y estado de cada pieza (RF-20). --}}
@php
    $paciente = $odontograma->historiaClinica->paciente;
    $editable = $odontograma->estado !== 'anulado';

    // Clase de un control, con borde rojo si el campo tiene error.
    $claseCampo = fn (string $campo) => 'dr-input'.($errors->has($campo) ? ' dr-input-error' : '');

    // Piezas del odontograma indexadas por código FDI, y nombres de todas las piezas.
    $detalles = $odontograma->detalles->keyBy('pieza_dental_id');
    $nombres = $odontograma->detalles->pluck('pieza.nombre', 'pieza_dental_id')
        ->union($piezasDisponibles->pluck('nombre', 'id'));

    // Color del texto según el fondo, para que el código FDI se lea sobre colores claros.
    $textoSobre = function (?string $hex): string {
        if (! $hex || ! preg_match('/^#[0-9A-Fa-f]{6}$/', $hex)) {
            return '#1F2933';
        }
        [$r, $g, $b] = sscanf($hex, '#%02x%02x%02x');

        return (0.299 * $r + 0.587 * $g + 0.114 * $b) > 160 ? '#1F2933' : '#FFFFFF';
    };

    // Esquema FDI visto de frente: el lado derecho del paciente queda a la izquierda.
    $arcadas = [
        'Arcada superior' => [range(18, 11), range(21, 28)],
        'Arcada inferior' => [range(48, 41), range(31, 38)],
    ];

    $conTemporales = $odontograma->tipo_denticion !== 'permanente'
        || $odontograma->detalles->contains(fn ($d) => $d->pieza->cuadrante >= 5);

    if ($conTemporales) {
        $arcadas['Arcada superior temporal'] = [range(55, 51), range(61, 65)];
        $arcadas['Arcada inferior temporal'] = [range(85, 81), range(71, 75)];
    }
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-cabecera-pagina :titulo="'Odontograma: '.$paciente->nombreCompleto()"
                           :subtitulo="ucfirst($odontograma->tipo).' del '.$odontograma->fecha_registro->format('d/m/Y').'. Haga clic en una pieza para cambiar su estado.'"
                           :volver="route('odontogramas.listar', $paciente->id)" volver-texto="Odontogramas" />
    </x-slot>

    {{-- abrir(): carga la pieza elegida en "p" y abre el modal de edición --}}
    <x-pagina x-data="{
                  p: {},
                  abrir(datos) { this.p = datos; this.$dispatch('open-modal', 'pieza'); },
              }">
        @if (session('exito'))
            <x-alerta tipo="exito">{{ session('exito') }}</x-alerta>
        @endif

        @if ($errors->any())
            <x-alerta tipo="error">
                <ul class="space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alerta>
        @endif

        @unless ($editable)
            <x-alerta tipo="aviso">Este odontograma está anulado: solo puede consultarse.</x-alerta>
        @endunless

        {{-- Datos del odontograma --}}
        <section class="dr-card p-5 sm:p-6" aria-labelledby="datos-odontograma">
            <h3 id="datos-odontograma" class="text-lg font-bold text-ink">Datos del odontograma</h3>

            <dl class="mt-5 grid grid-cols-1 gap-x-6 gap-y-5 text-sm sm:grid-cols-2 lg:grid-cols-5">
                <x-dato etiqueta="Fecha"><span class="tabular-nums">{{ $odontograma->fecha_registro->format('d/m/Y') }}</span></x-dato>
                <x-dato etiqueta="Tipo"><x-insignia :tono="$odontograma->tipo === 'inicial' ? 'marca' : 'info'">{{ ucfirst($odontograma->tipo) }}</x-insignia></x-dato>
                <x-dato etiqueta="Dentición">{{ ucfirst($odontograma->tipo_denticion) }} · {{ $odontograma->detalles->count() }} piezas</x-dato>
                <x-dato etiqueta="Odontólogo">{{ $odontograma->odontologo->nombres }} {{ $odontograma->odontologo->apellidos }}</x-dato>
                <x-dato etiqueta="Estado"><x-insignia :tono="$editable ? 'exito' : 'error'">{{ ucfirst($odontograma->estado) }}</x-insignia></x-dato>
                <x-dato etiqueta="Observaciones" class="sm:col-span-2 lg:col-span-5 !border-b-0 !pb-0">
                    <span class="whitespace-pre-line">{{ $odontograma->observaciones ?? '—' }}</span>
                </x-dato>
            </dl>
        </section>

        {{-- Odontograma gráfico: dos arcadas y cuatro cuadrantes (más los temporales) --}}
        <section class="dr-card overflow-hidden" aria-labelledby="grafico-odontograma">
            <header class="flex flex-col gap-1 border-b border-line bg-surface/60 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <h3 id="grafico-odontograma" class="text-lg font-bold text-ink">Piezas</h3>
                <p class="text-xs text-ink-soft">Derecha del paciente ← → Izquierda del paciente</p>
            </header>

            <div class="overflow-x-auto p-5 sm:p-6">
                <div class="mx-auto w-max space-y-6">
                    @foreach ($arcadas as $titulo => $cuadrantes)
                        <div @class(['border-t border-dashed border-line pt-6' => $loop->index === 2])>
                            <p class="mb-2 text-center text-xs font-semibold uppercase tracking-wide text-ink-soft">{{ $titulo }}</p>
                            <div class="flex items-center justify-center gap-3">
                                @foreach ($cuadrantes as $piezas)
                                    @if (! $loop->first)
                                        {{-- Línea media --}}
                                        <div class="h-12 w-px bg-ink-soft/40" aria-hidden="true"></div>
                                    @endif
                                    <div class="flex gap-1">
                                        @foreach ($piezas as $fdi)
                                            @php $d = $detalles->get($fdi); @endphp
                                            @if ($d)
                                                <button type="button"
                                                        class="h-11 w-11 rounded-lg border border-black/10 text-xs font-bold tabular-nums shadow-sm transition hover:scale-110 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-1 motion-reduce:transition-none motion-reduce:hover:scale-100"
                                                        style="background-color: {{ $d->estadoPieza->color_referencia ?? '#E1E8EB' }}; color: {{ $textoSobre($d->estadoPieza->color_referencia) }};"
                                                        title="{{ $fdi }} · {{ $d->estadoPieza->nombre }}"
                                                        aria-label="Pieza {{ $fdi }}: {{ $d->estadoPieza->nombre }}"
                                                        @click="abrir({{ Js::from([
                                                            'modo' => 'editar',
                                                            'detalle_id' => (string) $d->id,
                                                            'pieza_id' => (string) $fdi,
                                                            'nombre' => $d->pieza->nombre,
                                                            'estado_pieza_id' => (string) $d->estado_pieza_id,
                                                            'estado' => $d->estadoPieza->nombre,
                                                            'tratamiento_aplicado_id' => (string) ($d->tratamiento_aplicado_id ?? ''),
                                                            'observacion' => $d->observacion ?? '',
                                                        ]) }})">
                                                    {{ $fdi }}
                                                </button>
                                            @else
                                                {{-- Pieza que no está en el odontograma: al hacer clic se puede agregar --}}
                                                <button type="button"
                                                        class="h-11 w-11 rounded-lg border-2 border-dashed border-line text-xs tabular-nums text-ink-mute hover:border-brand-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
                                                        title="{{ $fdi }} · No registrada"
                                                        aria-label="Pieza {{ $fdi }}: no registrada"
                                                        @click="abrir({{ Js::from([
                                                            'modo' => 'agregar',
                                                            'pieza_id' => (string) $fdi,
                                                            'nombre' => $nombres->get($fdi, "Pieza {$fdi}"),
                                                            'estado_pieza_id' => (string) ($estados->first()?->id ?? ''),
                                                            'estado' => '',
                                                            'tratamiento_aplicado_id' => '',
                                                            'observacion' => '',
                                                        ]) }})">
                                                    {{ $fdi }}
                                                </button>
                                            @endif
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Leyenda de estados (RNF-26: color siempre con texto) --}}
            <div class="border-t border-line px-5 py-4 sm:px-6">
                <p class="text-xs font-semibold uppercase tracking-wide text-ink-soft">Leyenda</p>
                <ul class="mt-2 flex flex-wrap gap-x-5 gap-y-2 text-sm text-ink">
                    @foreach ($estados as $estado)
                        <li class="flex items-center gap-2">
                            <span class="h-4 w-4 shrink-0 rounded border border-black/10" style="background-color: {{ $estado->color_referencia ?? '#E1E8EB' }};" aria-hidden="true"></span>
                            {{ $estado->nombre }}
                        </li>
                    @endforeach
                    <li class="flex items-center gap-2">
                        <span class="h-4 w-4 shrink-0 rounded border-2 border-dashed border-line" aria-hidden="true"></span>
                        No registrada
                    </li>
                </ul>
            </div>
        </section>

        {{-- Agregar una pieza que no está en el odontograma --}}
        @if ($editable && $piezasDisponibles->isNotEmpty())
            <form method="POST" action="{{ route('odontogramas.detalle.guardar') }}" class="dr-card overflow-hidden" aria-labelledby="agregar-pieza">
                @csrf
                <input type="hidden" name="odontograma_id" value="{{ $odontograma->id }}">

                <header class="border-b border-line bg-surface/60 px-5 py-4 sm:px-6">
                    <h3 id="agregar-pieza" class="text-lg font-bold text-ink">Agregar pieza</h3>
                    <p class="mt-0.5 text-sm text-ink-soft">Solo se listan las piezas que todavía no están en el odontograma.</p>
                </header>

                <div class="space-y-5 p-5 sm:p-6">
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                        <x-campo name="nueva_pieza" label="Pieza" :requerido="true" :errores="$errors->get('pieza_dental_id')">
                            <select id="nueva_pieza" name="pieza_dental_id" class="{{ $claseCampo('pieza_dental_id') }}" required>
                                <option value="">Seleccione…</option>
                                @foreach ($piezasDisponibles as $pieza)
                                    <option value="{{ $pieza->id }}" @selected(old('pieza_dental_id') == $pieza->id)>{{ $pieza->codigo_fdi }} · {{ $pieza->nombre }}</option>
                                @endforeach
                            </select>
                        </x-campo>

                        <x-campo name="nuevo_estado" label="Estado" :requerido="true" :errores="$errors->get('estado_pieza_id')">
                            <select id="nuevo_estado" name="estado_pieza_id" class="{{ $claseCampo('estado_pieza_id') }}" required>
                                @foreach ($estados as $estado)
                                    <option value="{{ $estado->id }}" @selected(old('estado_pieza_id') == $estado->id)>{{ $estado->nombre }}</option>
                                @endforeach
                            </select>
                        </x-campo>

                        <x-campo name="nuevo_tratamiento" label="Tratamiento aplicado (opcional)" :errores="$errors->get('tratamiento_aplicado_id')">
                            <select id="nuevo_tratamiento" name="tratamiento_aplicado_id" class="{{ $claseCampo('tratamiento_aplicado_id') }}">
                                <option value="">Ninguno</option>
                                @foreach ($tratamientos as $t)
                                    <option value="{{ $t->id }}" @selected(old('tratamiento_aplicado_id') == $t->id)>{{ $t->tratamiento->nombre }} ({{ $t->fecha_inicio?->format('d/m/Y') }})</option>
                                @endforeach
                            </select>
                        </x-campo>
                    </div>

                    <x-campo name="nueva_observacion" label="Observación (opcional)" :errores="$errors->get('observacion')">
                        <textarea id="nueva_observacion" name="observacion" rows="2" class="{{ $claseCampo('observacion') }}">{{ old('observacion') }}</textarea>
                    </x-campo>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-line bg-surface/60 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <p class="text-xs text-ink-soft"><span class="text-red-700" aria-hidden="true">*</span> Campo obligatorio</p>
                    <x-primary-button class="w-full sm:w-auto"><x-icon name="plus" class="h-4 w-4" /> Agregar pieza</x-primary-button>
                </div>
            </form>
        @endif

        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('odontogramas.listar', $paciente->id) }}" class="btn btn-secondary">
                <x-icon name="arrow-left" class="h-4 w-4" /> Volver a los odontogramas
            </a>
            <a href="{{ route('odontogramas.comparar', $paciente->id) }}" class="btn btn-secondary">
                <x-icon name="refresh" class="h-4 w-4" /> Comparar
            </a>
        </div>

        {{-- Modal de la pieza seleccionada: modificar o agregar (el id viaja oculto) --}}
        <x-modal name="pieza" maxWidth="lg" focusable>
            <div class="space-y-5 p-6">
                <header class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 font-display text-sm font-bold tabular-nums text-brand-700" x-text="p.pieza_id"></span>
                    <div class="min-w-0">
                        <h2 class="text-lg font-bold text-ink">Pieza <span x-text="p.pieza_id"></span></h2>
                        <p class="mt-0.5 break-words text-sm text-ink-soft" x-text="p.nombre"></p>
                    </div>
                </header>

                @if ($editable)
                    <form method="POST"
                          :action="p.modo === 'editar' ? @js(route('odontogramas.detalle.modificar')) : @js(route('odontogramas.detalle.guardar'))"
                          class="space-y-5">
                        @csrf
                        {{-- Al modificar viaja el id del detalle; al agregar, el odontograma y la pieza --}}
                        <template x-if="p.modo === 'editar'">
                            <input type="hidden" name="id" :value="p.detalle_id">
                        </template>
                        <template x-if="p.modo === 'agregar'">
                            <div>
                                <input type="hidden" name="odontograma_id" value="{{ $odontograma->id }}">
                                <input type="hidden" name="pieza_dental_id" :value="p.pieza_id">
                            </div>
                        </template>

                        <x-campo name="panel_estado" label="Estado" :requerido="true">
                            <select id="panel_estado" name="estado_pieza_id" x-model="p.estado_pieza_id" class="dr-input" required>
                                @foreach ($estados as $estado)
                                    <option value="{{ $estado->id }}">{{ $estado->nombre }}</option>
                                @endforeach
                            </select>
                        </x-campo>

                        <x-campo name="panel_tratamiento" label="Tratamiento aplicado (opcional)" ayuda="Vincula la pieza con un tratamiento del paciente (RF-20).">
                            <select id="panel_tratamiento" name="tratamiento_aplicado_id" x-model="p.tratamiento_aplicado_id" class="dr-input">
                                <option value="">Ninguno</option>
                                @foreach ($tratamientos as $t)
                                    <option value="{{ $t->id }}">{{ $t->tratamiento->nombre }} ({{ $t->fecha_inicio?->format('d/m/Y') }})</option>
                                @endforeach
                            </select>
                        </x-campo>

                        <x-campo name="panel_observacion" label="Observación (opcional)">
                            <textarea id="panel_observacion" name="observacion" rows="3" x-model="p.observacion" class="dr-input"></textarea>
                        </x-campo>

                        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                            <x-secondary-button @click="$dispatch('close-modal', 'pieza')">Cerrar</x-secondary-button>
                            <x-primary-button><span x-text="p.modo === 'editar' ? 'Guardar' : 'Agregar pieza'"></span></x-primary-button>
                        </div>
                    </form>

                    {{-- Quitar la pieza del odontograma (el id viaja oculto) --}}
                    <form x-show="p.modo === 'editar'" method="POST" action="{{ route('odontogramas.detalle.quitar') }}"
                          class="border-t border-line pt-5"
                          @submit="if (! confirm('¿Quitar la pieza ' + p.pieza_id + ' del odontograma?')) $event.preventDefault()">
                        @csrf
                        <input type="hidden" name="id" :value="p.detalle_id">
                        <x-danger-button :suave="true" class="w-full sm:w-auto"><x-icon name="ban" class="h-4 w-4" /> Quitar del odontograma</x-danger-button>
                    </form>
                @else
                    <dl class="grid grid-cols-1 gap-y-4 text-sm">
                        <x-dato etiqueta="Estado"><span x-text="p.estado || 'No registrada'"></span></x-dato>
                        <x-dato etiqueta="Observación" class="!border-b-0 !pb-0"><span class="whitespace-pre-line" x-text="p.observacion || '—'"></span></x-dato>
                    </dl>
                    <div class="flex justify-end">
                        <x-secondary-button @click="$dispatch('close-modal', 'pieza')">Cerrar</x-secondary-button>
                    </div>
                @endif
            </div>
        </x-modal>
    </x-pagina>
</x-app-layout>
