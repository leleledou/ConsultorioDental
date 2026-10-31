{{-- Resultado del reporte y botones de CU45, con los mismos parámetros del reporte en pantalla --}}
@php
    use App\Services\ReporteService;

    $claseEnlace = 'inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700';
    $parametros = request()->query() + ['reporte' => $reporte];
@endphp

@if ($resultado)
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900 dark:text-gray-100 space-y-4">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $resultado['titulo'] }}</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $resultado['periodo'] }}</p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('reportes.exportar', $parametros + ['formato' => 'pdf']) }}" class="{{ $claseEnlace }}">Exportar PDF</a>
                    <a href="{{ route('reportes.exportar', $parametros + ['formato' => 'excel']) }}" class="{{ $claseEnlace }}">Exportar Excel</a>
                </div>
            </div>

            @if ($resultado['filas'] === [])
                <p class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">No hay registros para los filtros elegidos</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead>
                            <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                @foreach ($resultado['columnas'] as $encabezado)
                                    <th class="px-3 py-2">{{ $encabezado }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach ($resultado['filas'] as $fila)
                                <tr class="align-top">
                                    @foreach ($resultado['columnas'] as $clave => $encabezado)
                                        <td class="px-3 py-3">{{ ReporteService::formatear($resultado, $clave, $fila[$clave]) }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <dl class="grid grid-cols-2 sm:grid-cols-4 gap-x-6 gap-y-4 text-sm">
                @foreach ($resultado['totales'] as $etiqueta => $valor)
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ $etiqueta }}</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ ReporteService::formatear($resultado, $etiqueta, $valor) }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </div>
@endif
