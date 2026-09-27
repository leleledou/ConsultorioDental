{{-- CU07 paso 5: detalle de un registro de la bitácora (solo lectura, RN-02). --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Registro de bitácora #{{ $registro->id }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-4 text-sm">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Usuario</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">
                            @if ($registro->usuario)
                                {{ $registro->usuario->nombres }} {{ $registro->usuario->apellidos }} ({{ $registro->usuario->usuario }})
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Usuario digitado en el login</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $registro->usuario_digitado ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Acción</dt>
                        <dd class="font-mono font-medium text-gray-900 dark:text-gray-100">{{ $registro->accion }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Tabla afectada</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $registro->tabla_afectada ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Registro afectado</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $registro->registro_id ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">IP</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $registro->ip }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Fecha</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $registro->fecha_hora->format('d/m/Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Hora</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $registro->fecha_hora->format('H:i:s') }}</dd>
                    </div>
                </dl>
            </div>

            <div>
                <a href="{{ route('bitacora.listar') }}" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700">
                    Volver a la bitácora
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
