{{-- CU03 Cambiar contraseña (basada en el formulario de contraseña del perfil de Breeze). --}}
<x-app-layout>
    <x-slot name="header">
        <x-cabecera-pagina titulo="Cambiar contraseña" subtitulo="Elija una contraseña nueva para su cuenta." />
    </x-slot>

    <x-pagina ancho="3xl">
        {{-- Aviso destacado (p. ej. obligación de reemplazar la contraseña temporal, CU06) --}}
        @if (session('aviso'))
            <x-alerta tipo="aviso">{{ session('aviso') }}</x-alerta>
        @endif

        <section class="dr-card overflow-hidden" aria-labelledby="titulo-contrasena">
            <header class="flex items-start gap-4 border-b border-line bg-surface/60 px-5 py-4 sm:px-6">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700" aria-hidden="true">
                    <x-icon name="key" class="h-6 w-6" />
                </span>
                <div>
                    <h3 id="titulo-contrasena" class="text-lg font-bold text-ink">Cambiar contraseña</h3>
                    <p class="mt-0.5 text-sm text-ink-soft">Al guardar, se cerrarán sus sesiones abiertas en otros equipos.</p>
                </div>
            </header>

            <form method="POST" action="{{ route('contrasena.guardar') }}" class="p-5 sm:p-6">
                @csrf

                <div class="max-w-xl space-y-5">
                    @if ($errors->any())
                        <x-alerta tipo="error">Revise los datos marcados en el formulario.</x-alerta>
                    @endif

                    <!-- Contraseña actual -->
                    <x-campo name="contrasena_actual" label="Contraseña actual" :requerido="true" :errores="$errors->get('contrasena_actual')">
                        <x-text-input id="contrasena_actual" name="contrasena_actual" type="password" autocomplete="current-password" required :error="$errors->has('contrasena_actual')" />
                    </x-campo>

                    <div class="border-t border-line pt-5">
                        <!-- Nueva contraseña (ayuda con la política RN-01) -->
                        <div class="space-y-5">
                            <x-campo name="password" label="Nueva contraseña" :requerido="true"
                                     ayuda="Mínimo 8 caracteres, con mayúsculas, minúsculas, números y caracteres especiales, y sin espacios."
                                     :errores="$errors->get('password')">
                                <x-text-input id="password" name="password" type="password" autocomplete="new-password" required :error="$errors->has('password')" />
                            </x-campo>

                            <!-- Confirmar nueva contraseña -->
                            <x-campo name="password_confirmation" label="Confirmar nueva contraseña" :requerido="true" :errores="$errors->get('password_confirmation')">
                                <x-text-input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required :error="$errors->has('password_confirmation')" />
                            </x-campo>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex flex-col-reverse gap-3 border-t border-line pt-5 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs text-ink-soft"><span class="text-red-700" aria-hidden="true">*</span> Campo obligatorio</p>
                    <x-primary-button class="w-full sm:w-auto">Guardar</x-primary-button>
                </div>
            </form>
        </section>
    </x-pagina>
</x-app-layout>
