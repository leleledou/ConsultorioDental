{{-- CU25 Registrar odontograma: odontograma gráfico (RF-23) y estado de cada pieza (RF-20). --}}
@php
    $claseCampo = 'mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm';
    $claseEnlace = 'inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700';

    $paciente = $odontograma->historiaClinica->paciente;
    $editable = $odontograma->estado !== 'anulado';

    // Piezas del odontograma indexadas por código FDI, y nombres de todas las piezas.
    $detalles = $odontograma->detalles->keyBy('pieza_dental_id');
    $nombres = $odontograma->detalles->pluck('pieza.nombre', 'pieza_dental_id')
        ->union($piezasDisponibles->pluck('nombre', 'id'));

    // Color del texto según el fondo, para que el código FDI se lea sobre colores claros.
    $textoSobre = function (?string $hex): string {
        if (! $hex || ! preg_match('/^#[0-9A-Fa-f]{6}$/', $hex)) {
            return '#111827';
        }
        [$r, $g, $b] = sscanf($hex, '#%02x%02x%02x');

        return (0.299 * $r + 0.587 * $g + 0.114 * $b) > 160 ? '#111827' : '#FFFFFF';
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
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Odontograma: {{ $paciente->nombreCompleto() }} — {{ $odontograma->fecha_registro->format('d/m/Y') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6"
             x-data="{
                 abierta: false,
                 p: {},
                 abrir(datos) { this.p = datos; this.abierta = true; },
             }"
             @keydown.escape.window="abierta = false">
            @if (session('exito'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm font-medium text-green-800 dark:text-green-200">
                    {{ session('exito') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm font-medium text-red-800 dark:text-red-200">
                    <ul class="space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @unless ($editable)
                <div class="rounded-md bg-yellow-50 dark:bg-yellow-900/30 p-4 text-sm font-medium text-yellow-800 dark:text-yellow-200">
                    Este odontograma está anulado: solo puede consultarse.
                </div>
            @endunless

            {{-- Datos del odontograma --}}
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <dl class="grid grid-cols-2 sm:grid-cols-5 gap-x-6 gap-y-4 text-sm">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Fecha</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $odontograma->fecha_registro->format('d/m/Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Tipo</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ ucfirst($odontograma->tipo) }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Dentición</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ ucfirst($odontograma->tipo_denticion) }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Odontólogo</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $odontograma->odontologo->nombres }} {{ $odontograma->odontologo->apellidos }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Estado</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ ucfirst($odontograma->estado) }}</dd>
                    </div>
                </dl>
                @if ($odontograma->observaciones)
                    <p class="mt-4 text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $odontograma->observaciones }}</p>
                @endif
            </div>

            {{-- Odontograma gráfico: dos arcadas y cuatro cuadrantes (más los temporales) --}}
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="flex items-baseline justify-between gap-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Piezas</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Derecha del paciente ← → Izquierda del paciente</p>
                </div>

                <div class="mt-6 overflow-x-auto">
                    <div class="mx-auto w-max space-y-6">
                        @foreach ($arcadas as $titulo => $cuadrantes)
                            <div @class(['pt-6 border-t border-dashed border-gray-300 dark:border-gray-600' => $loop->index === 2])>
                                <p class="mb-2 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ $titulo }}</p>
                                <div class="flex items-center justify-center gap-3">
                                    @foreach ($cuadrantes as $piezas)
                                        @if (! $loop->first)
                                            {{-- Línea media --}}
                                            <div class="h-12 w-px bg-gray-400 dark:bg-gray-500"></div>
                                        @endif
                                        <div class="flex gap-1">
                                            @foreach ($piezas as $fdi)
                                                @php $d = $detalles->get($fdi); @endphp
                                                @if ($d)
                                                    <button type="button"
                                                            class="h-11 w-11 rounded-md border border-black/10 text-xs font-bold shadow-sm transition hover:scale-110 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1"
                                                            style="background-color: {{ $d->estadoPieza->color_referencia ?? '#E5E7EB' }}; color: {{ $textoSobre($d->estadoPieza->color_referencia) }};"
                                                            title="{{ $fdi }} · {{ $d->estadoPieza->nombre }}"
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
                                                            class="h-11 w-11 rounded-md border-2 border-dashed border-gray-300 dark:border-gray-600 text-xs text-gray-400 hover:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                                            title="{{ $fdi }} · No registrada"
                                                            @click="abrir({{ Js::from([
                                                                'modo' => 'agregar',
                                                                'pieza_id' => (string) $fdi,
                                                                'nombre' => $nombres->get($fdi, "Pieza {$fdi}"),
                                                                'estado_pieza_id' => (string) ($estados->first()?->id ?? ''),
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

                {{-- Leyenda de estados --}}
                <div class="mt-8 border-t border-gray-200 dark:border-gray-700 pt-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Leyenda</p>
                    <ul class="mt-2 flex flex-wrap gap-x-5 gap-y-2 text-sm text-gray-700 dark:text-gray-300">
                        @foreach ($estados as $estado)
                            <li class="flex items-center gap-2">
                                <span class="inline-block h-4 w-4 rounded border border-black/10" style="background-color: {{ $estado->color_referencia ?? '#E5E7EB' }};"></span>
                                {{ $estado->nombre }}
                            </li>
                        @endforeach
                        <li class="flex items-center gap-2">
                            <span class="inline-block h-4 w-4 rounded border-2 border-dashed border-gray-300 dark:border-gray-600"></span>
                            No registrada
                        </li>
                    </ul>
                </div>
            </div>

            {{-- Agregar una pieza que no está en el odontograma --}}
            @if ($editable && $piezasDisponibles->isNotEmpty())
                <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Agregar pieza</h2>
                        </header>

                        <form method="POST" action="{{ route('odontogramas.detalle.guardar') }}" class="mt-6 space-y-6">
                            @csrf
                            <input type="hidden" name="odontograma_id" value="{{ $odontograma->id }}">

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                                <div>
                                    <x-input-label for="nueva_pieza" value="Pieza" />
                                    <select id="nueva_pieza" name="pieza_dental_id" class="{{ $claseCampo }}" required>
                                        <option value="">Seleccione…</option>
                                        @foreach ($piezasDisponibles as $pieza)
                                            <option value="{{ $pieza->id }}" @selected(old('pieza_dental_id') == $pieza->id)>{{ $pieza->codigo_fdi }} · {{ $pieza->nombre }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('pieza_dental_id')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="nuevo_estado" value="Estado" />
                                    <select id="nuevo_estado" name="estado_pieza_id" class="{{ $claseCampo }}" required>
                                        @foreach ($estados as $estado)
                                            <option value="{{ $estado->id }}" @selected(old('estado_pieza_id') == $estado->id)>{{ $estado->nombre }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('estado_pieza_id')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="nuevo_tratamiento" value="Tratamiento aplicado (opcional)" />
                                    <select id="nuevo_tratamiento" name="tratamiento_aplicado_id" class="{{ $claseCampo }}">
                                        <option value="">Ninguno</option>
                                        @foreach ($tratamientos as $t)
                                            <option value="{{ $t->id }}" @selected(old('tratamiento_aplicado_id') == $t->id)>{{ $t->tratamiento->nombre }} ({{ $t->fecha_inicio?->format('d/m/Y') }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div>
                                <x-input-label for="nueva_observacion" value="Observación (opcional)" />
                                <textarea id="nueva_observacion" name="observacion" rows="2" class="{{ $claseCampo }}">{{ old('observacion') }}</textarea>
                            </div>

                            <x-primary-button>Agregar</x-primary-button>
                        </form>
                    </section>
                </div>
            @endif

            <div class="flex flex-wrap items-center gap-4">
                <a href="{{ route('odontogramas.listar', $paciente->id) }}" class="{{ $claseEnlace }}">Volver a los odontogramas</a>
                <a href="{{ route('odontogramas.comparar', $paciente->id) }}" class="{{ $claseEnlace }}">Comparar</a>
            </div>

            {{-- Panel lateral de la pieza seleccionada --}}
            <div x-show="abierta" x-cloak class="fixed inset-0 z-50 flex justify-end">
                <div class="absolute inset-0 bg-gray-900/50" @click="abierta = false"></div>

                <aside class="relative h-full w-full max-w-md overflow-y-auto bg-white dark:bg-gray-800 p-6 shadow-xl"
                       x-show="abierta"
                       x-transition:enter="transition ease-out duration-200"
                       x-transition:enter-start="translate-x-full"
                       x-transition:enter-end="translate-x-0">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                Pieza <span x-text="p.pieza_id"></span>
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400" x-text="p.nombre"></p>
                        </div>
                        <x-secondary-button @click="abierta = false">Cerrar</x-secondary-button>
                    </div>

                    @if ($editable)
                        <form method="POST"
                              :action="p.modo === 'editar' ? @js(route('odontogramas.detalle.modificar')) : @js(route('odontogramas.detalle.guardar'))"
                              class="mt-6 space-y-6">
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

                            <div>
                                <x-input-label for="panel_estado" value="Estado" />
                                <select id="panel_estado" name="estado_pieza_id" x-model="p.estado_pieza_id" class="{{ $claseCampo }}" required>
                                    @foreach ($estados as $estado)
                                        <option value="{{ $estado->id }}">{{ $estado->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-input-label for="panel_tratamiento" value="Tratamiento aplicado (opcional)" />
                                <select id="panel_tratamiento" name="tratamiento_aplicado_id" x-model="p.tratamiento_aplicado_id" class="{{ $claseCampo }}">
                                    <option value="">Ninguno</option>
                                    @foreach ($tratamientos as $t)
                                        <option value="{{ $t->id }}">{{ $t->tratamiento->nombre }} ({{ $t->fecha_inicio?->format('d/m/Y') }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-input-label for="panel_observacion" value="Observación (opcional)" />
                                <textarea id="panel_observacion" name="observacion" rows="3" x-model="p.observacion" class="{{ $claseCampo }}"></textarea>
                            </div>

                            <x-primary-button>
                                <span x-text="p.modo === 'editar' ? 'Guardar' : 'Agregar pieza'"></span>
                            </x-primary-button>
                        </form>

                        {{-- Quitar la pieza (el id viaja oculto) --}}
                        <form x-show="p.modo === 'editar'" method="POST" action="{{ route('odontogramas.detalle.quitar') }}" class="mt-4"
                              @submit="if (! confirm('¿Quitar la pieza ' + p.pieza_id + ' del odontograma?')) $event.preventDefault()">
                            @csrf
                            <input type="hidden" name="id" :value="p.detalle_id">
                            <x-danger-button>Quitar</x-danger-button>
                        </form>
                    @else
                        <dl class="mt-6 space-y-4 text-sm">
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">Estado</dt>
                                <dd class="font-medium text-gray-900 dark:text-gray-100" x-text="p.estado || 'No registrada'"></dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">Observación</dt>
                                <dd class="text-gray-900 dark:text-gray-100 whitespace-pre-line" x-text="p.observacion || '—'"></dd>
                            </div>
                        </dl>
                    @endif
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
