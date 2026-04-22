<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Consumo</h2></x-slot>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        <form method="GET" class="bg-white p-3 rounded-md shadow-sm flex flex-wrap gap-2 items-end">
            <div>
                <label class="text-xs text-gray-600">Desde</label>
                <input type="date" name="from" value="{{ $from->format('Y-m-d') }}" class="border rounded-md px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-600">Hasta</label>
                <input type="date" name="to" value="{{ $to->format('Y-m-d') }}" class="border rounded-md px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-600">Agrupar por</label>
                <select name="group_by" class="border rounded-md px-3 py-1.5 text-sm">
                    <option value="location" @selected($groupBy==='location')>Ubicación (máquina / línea)</option>
                    <option value="work_order" @selected($groupBy==='work_order')>Orden de producción</option>
                </select>
            </div>
            <button class="bg-gray-800 text-white px-3 py-1.5 rounded-md text-sm">Aplicar</button>
        </form>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-lg shadow">
                <div class="px-4 py-3 border-b"><h3 class="font-semibold">Por {{ $groupBy }}</h3></div>
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-600">
                        <tr><th class="px-4 py-2 text-left">Grupo</th><th class="px-4 py-2">Movimientos</th><th class="px-4 py-2">Unidades</th></tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($rows as $r)
                            <tr>
                                <td class="px-4 py-2 font-medium">{{ $r->bucket ?? '—' }}</td>
                                <td class="px-4 py-2 text-center">{{ $r->movs }}</td>
                                <td class="px-4 py-2 text-center">{{ $r->total }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-8 text-center text-gray-500">Sin datos en el rango.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="bg-white rounded-lg shadow">
                <div class="px-4 py-3 border-b"><h3 class="font-semibold">Top herramientas</h3></div>
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-600">
                        <tr><th class="px-4 py-2 text-left">Herramienta</th><th class="px-4 py-2">Unidades</th><th class="px-4 py-2">Costo</th></tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($byTool as $t)
                            <tr>
                                <td class="px-4 py-2">
                                    <div class="font-medium">{{ $t->name }}</div>
                                    <div class="text-xs font-mono text-gray-500">{{ $t->code }}</div>
                                </td>
                                <td class="px-4 py-2 text-center">{{ $t->total }}</td>
                                <td class="px-4 py-2 text-right">{{ $t->cost ? '$'.number_format($t->cost, 2) : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-8 text-center text-gray-500">Sin datos.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
