<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Uso de herramientas</h2></x-slot>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow">
            <div class="px-4 py-3 border-b"><h3 class="font-semibold">Más usadas</h3></div>
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-600">
                    <tr><th class="px-4 py-2 text-left">Herramienta</th><th class="px-4 py-2">Veces</th></tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($most as $t)
                        <tr>
                            <td class="px-4 py-2">
                                <div class="font-medium">{{ $t->name }}</div>
                                <div class="text-xs font-mono text-gray-500">{{ $t->code }}</div>
                            </td>
                            <td class="px-4 py-2 text-center">{{ $t->uses }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="bg-white rounded-lg shadow">
            <div class="px-4 py-3 border-b"><h3 class="font-semibold">Subutilizadas</h3></div>
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-600">
                    <tr><th class="px-4 py-2 text-left">Herramienta</th><th class="px-4 py-2">Veces</th></tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($least as $t)
                        <tr>
                            <td class="px-4 py-2">
                                <div class="font-medium">{{ $t->name }}</div>
                                <div class="text-xs font-mono text-gray-500">{{ $t->code }}</div>
                            </td>
                            <td class="px-4 py-2 text-center">{{ $t->uses }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
