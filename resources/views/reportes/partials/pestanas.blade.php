{{-- Pestañas entre los reportes clínicos, de agenda y financieros --}}
<div class="flex gap-6 border-b border-gray-200 dark:border-gray-700">
    <x-nav-link :href="route('reportes.clinicos')" :active="request()->routeIs('reportes.clinicos')">Reportes clínicos</x-nav-link>
    <x-nav-link :href="route('reportes.agenda')" :active="request()->routeIs('reportes.agenda')">Reportes de agenda</x-nav-link>
    <x-nav-link :href="route('reportes.financieros')" :active="request()->routeIs('reportes.financieros')">Reportes financieros</x-nav-link>
</div>

@if ($errors->any())
    <div class="rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm font-medium text-red-800 dark:text-red-200">
        <ul class="space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
