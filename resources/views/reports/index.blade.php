<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Reportes</h2></x-slot>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-2 gap-6">
        <a href="{{ route('reports.consumption') }}" class="bg-white rounded-lg shadow p-6 hover:shadow-md transition">
            <div class="text-indigo-600 text-2xl">📊</div>
            <h3 class="font-semibold mt-2">Consumo</h3>
            <p class="text-sm text-gray-600 mt-1">Por máquina, línea, orden de producción. Costos asociados.</p>
        </a>
        <a href="{{ route('reports.usage') }}" class="bg-white rounded-lg shadow p-6 hover:shadow-md transition">
            <div class="text-emerald-600 text-2xl">⚙️</div>
            <h3 class="font-semibold mt-2">Uso de herramientas</h3>
            <p class="text-sm text-gray-600 mt-1">Más usadas vs subutilizadas. Ranking.</p>
        </a>
    </div>
</x-app-layout>
