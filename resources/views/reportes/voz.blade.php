{{-- Reportes por voz: entrada alternativa de CU42, CU43 y CU44 (no es un caso de uso nuevo).
     El navegador pasa la voz a texto; el backend interpreta la frase y genera el reporte estático. --}}
@php
    $ejemplos = [
        'Ingresos de octubre por método de pago',
        'Tratamientos con saldo pendiente',
        'Agenda de citas de esta semana',
        'Citas no asistidas de los últimos 90 días',
        'Pacientes atendidos este mes',
        'Materiales utilizados en julio',
    ];

    // Resumen corto para leer en voz alta: nombre, periodo y cantidad de filas (nunca la tabla).
    $resumen = null;

    if ($resultado) {
        $fecha = fn ($valor) => \Carbon\Carbon::parse($valor)->locale('es')->isoFormat('D [de] MMMM [de] YYYY');
        $filas = count($resultado['filas']);

        $resumen = $resultado['titulo'].'. Del '.$fecha(request('fecha_inicio')).' al '.$fecha(request('fecha_fin')).'. '
            .match (true) {
                $filas === 0 => 'No hay registros para el periodo.',
                $filas === 1 => 'Se encontró un registro.',
                default => "Se encontraron {$filas} registros.",
            };
    }
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-cabecera-pagina titulo="Reportes por voz" subtitulo="Pida un reporte con sus palabras: el sistema lo interpreta y lo genera" />
    </x-slot>

    <x-pagina>
        @include('reportes.partials.pestanas')

        <div class="space-y-6"
             x-data="{
                    frase: {{ Js::from($frase) }},
                    soportado: !! (window.SpeechRecognition || window.webkitSpeechRecognition),
                    escuchando: false,
                    aviso: '',
                    leer: false,
                    reconocimiento: null,
                    resumen: {{ Js::from($resumen) }},
                    init() {
                        try { this.leer = localStorage.getItem('dentalrox.voz.leer') === '1'; } catch (e) {}
                        this.$watch('leer', (valor) => {
                            try { localStorage.setItem('dentalrox.voz.leer', valor ? '1' : '0'); } catch (e) {}
                            if (! valor && 'speechSynthesis' in window) { window.speechSynthesis.cancel(); }
                        });
                        if (this.leer && this.resumen) { this.hablar(this.resumen); }
                    },
                    alternar() {
                        this.escuchando ? this.detener() : this.escuchar();
                    },
                    escuchar() {
                        const Reconocimiento = window.SpeechRecognition || window.webkitSpeechRecognition;
                        if (! Reconocimiento) { this.soportado = false; return; }
                        this.aviso = '';
                        const rec = new Reconocimiento();
                        rec.lang = 'es-BO';
                        rec.interimResults = true;
                        rec.continuous = false;
                        rec.onresult = (evento) => {
                            let texto = '';
                            for (const resultado of evento.results) { texto += resultado[0].transcript; }
                            this.frase = texto.trim().slice(0, 200);
                        };
                        rec.onerror = (evento) => {
                            this.aviso = {
                                'not-allowed': 'No se dio permiso para usar el micrófono. Puede escribir la consulta.',
                                'service-not-allowed': 'No se dio permiso para usar el micrófono. Puede escribir la consulta.',
                                'no-speech': 'No se escuchó ninguna consulta. Pulse el micrófono e intente de nuevo.',
                                'audio-capture': 'No se encontró un micrófono. Puede escribir la consulta.',
                                'network': 'No se pudo reconocer la voz por un problema de conexión. Puede escribir la consulta.',
                                'aborted': '',
                            }[evento.error] ?? 'No se pudo reconocer la voz. Intente de nuevo o escriba la consulta.';
                        };
                        rec.onend = () => { this.escuchando = false; };
                        try {
                            rec.start();
                            this.reconocimiento = rec;
                            this.escuchando = true;
                        } catch (e) {
                            this.escuchando = false;
                            this.aviso = 'No se pudo iniciar el micrófono. Puede escribir la consulta.';
                        }
                    },
                    detener() {
                        if (this.reconocimiento) { this.reconocimiento.stop(); }
                        this.escuchando = false;
                    },
                    hablar(texto) {
                        if (! ('speechSynthesis' in window)) { return; }
                        const voz = new SpeechSynthesisUtterance(texto);
                        voz.lang = 'es-BO';
                        window.speechSynthesis.cancel();
                        window.speechSynthesis.speak(voz);
                    },
             }">
        <section class="dr-card overflow-hidden" aria-labelledby="consulta-voz">
            <header class="border-b border-line bg-surface/60 px-5 py-4 sm:px-6">
                <h3 id="consulta-voz" class="text-lg font-bold text-ink">Consulta</h3>
                <p class="mt-0.5 text-sm text-ink-soft">Pulse el micrófono y diga qué reporte necesita, o escríbalo en el cuadro.</p>
            </header>

            <form method="POST" action="{{ route('reportes.voz.interpretar') }}" class="space-y-6 p-5 sm:p-6" @submit="detener()">
                @csrf

                {{-- Micrófono (solo si el navegador reconoce voz) --}}
                <div x-show="soportado" class="flex flex-col items-center gap-3 text-center">
                    <button type="button" @click="alternar()"
                            :aria-pressed="escuchando.toString()"
                            :aria-label="escuchando ? 'Detener la escucha' : 'Dictar la consulta'"
                            :class="escuchando
                                ? 'bg-red-600 text-white ring-8 ring-red-100 animate-pulse hover:bg-red-700'
                                : 'bg-brand-600 text-white ring-8 ring-brand-50 hover:bg-brand-700'"
                            class="flex h-20 w-20 items-center justify-center rounded-full shadow-card transition focus:outline-none focus-visible:ring-brand-300">
                        <x-icon name="mic" class="h-9 w-9" />
                    </button>
                    <p class="text-sm font-semibold" :class="escuchando ? 'text-red-700' : 'text-ink-soft'" aria-live="polite"
                       x-text="escuchando ? 'Escuchando… diga su consulta' : 'Pulse para dictar'"></p>
                </div>

                <x-alerta tipo="aviso" x-show="! soportado" x-cloak>
                    Su navegador no permite el reconocimiento de voz. Puede escribir la consulta en el cuadro.
                </x-alerta>

                <template x-if="aviso">
                    <x-alerta tipo="aviso"><span x-text="aviso"></span></x-alerta>
                </template>

                <x-campo name="frase" label="Consulta" :requerido="true" ayuda="Si el sistema entendió mal, corrija el texto antes de generar el reporte." :errores="$errors->get('frase')">
                    <textarea id="frase" name="frase" rows="2" maxlength="200" required x-model="frase"
                              placeholder="Por ejemplo: ingresos de octubre por método de pago"
                              @class(['dr-input resize-y', 'dr-input-error' => $errors->has('frase')])
                              @if ($errors->has('frase')) aria-invalid="true" @endif></textarea>
                </x-campo>

                {{-- Ejemplos: llenan el cuadro --}}
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-brand-800">Ejemplos</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($ejemplos as $ejemplo)
                            <button type="button" class="btn btn-secondary btn-sm rounded-full" @click="frase = {{ Js::from($ejemplo) }}">{{ $ejemplo }}</button>
                        @endforeach
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-4 border-t border-line pt-5 sm:flex-row sm:items-center sm:justify-between">
                    <label class="flex min-h-11 cursor-pointer items-center gap-3 rounded-xl border border-line px-4 text-sm font-medium text-ink has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50">
                        <input type="checkbox" x-model="leer" class="h-4 w-4 rounded border-line text-brand-600 focus:ring-brand-500">
                        Leer el resultado en voz alta
                    </label>

                    <button type="submit" class="btn btn-primary w-full sm:w-auto">Generar reporte</button>
                </div>
            </form>
        </section>

        {{-- Lo que entendió el sistema --}}
        @if ($interpretacion && $resultado)
            <div class="flex items-start gap-3 rounded-xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm text-brand-900" role="status">
                <x-icon name="check-circle" class="mt-0.5 h-5 w-5 shrink-0 text-brand-700" />
                <p class="min-w-0">
                    <span class="font-semibold">El sistema entendió:</span>
                    {{ $resultado['titulo'] }}, del {{ \Carbon\Carbon::parse(request('fecha_inicio'))->format('d/m/Y') }} al {{ \Carbon\Carbon::parse(request('fecha_fin'))->format('d/m/Y') }}.
                    @if ($frase !== '')
                        <span class="block text-brand-800">Consulta: «{{ $frase }}»</span>
                    @endif
                </p>
                {{-- Algunos navegadores no leen en voz alta sin un clic: este botón lo permite siempre --}}
                <button type="button" x-show="'speechSynthesis' in window" x-cloak @click="hablar(resumen)"
                        class="btn btn-secondary btn-sm ms-auto shrink-0">Escuchar resumen</button>
            </div>
        @endif

        {{-- Resultado y exportación (CU45) con los mismos parámetros del reporte en pantalla --}}
        @include('reportes.partials.resultado')
        </div>
    </x-pagina>
</x-app-layout>
