<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800">Herramientas</h2>
            <div class="flex gap-2">
                <a href="{{ route('labels.sheet') }}" target="_blank" class="bg-emerald-600 text-white px-3 py-2 rounded-md text-sm hover:bg-emerald-700">🖨 Imprimir hoja QR</a>
                @can('tools.create')
                    <a href="{{ route('tools.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700">+ Nueva</a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        <form method="GET" class="bg-white p-3 rounded-md shadow-sm flex flex-wrap gap-2 items-end">
            <div>
                <label class="text-xs text-gray-600">Buscar</label>
                <input name="q" value="{{ request('q') }}" placeholder="Código o nombre" class="border rounded-md px-3 py-1.5 text-sm w-60" />
            </div>
            <div>
                <label class="text-xs text-gray-600">Categoría</label>
                <select name="category_id" class="border rounded-md px-3 py-1.5 text-sm">
                    <option value="">Todas</option>
                    @foreach($categories as $c)
                        <option value="{{ $c->id }}" @selected(request('category_id') == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-600">Tipo</label>
                <select name="type" class="border rounded-md px-3 py-1.5 text-sm">
                    <option value="">Todos</option>
                    <option value="durable" @selected(request('type') == 'durable')>Durable</option>
                    <option value="consumible" @selected(request('type') == 'consumible')>Consumible</option>
                </select>
            </div>
            <label class="inline-flex items-center text-sm gap-1">
                <input type="checkbox" name="low_stock" value="1" @checked(request('low_stock'))> Stock bajo
            </label>
            <button class="bg-gray-800 text-white px-3 py-1.5 rounded-md text-sm">Filtrar</button>
        </form>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-2 text-left">Código</th>
                        <th class="px-4 py-2 text-left">Nombre</th>
                        <th class="px-4 py-2">Categoría</th>
                        <th class="px-4 py-2">Tipo</th>
                        <th class="px-4 py-2">Disp / Total</th>
                        <th class="px-4 py-2">Stock mín.</th>
                        <th class="px-4 py-2">Ubicación</th>
                        <th class="px-4 py-2">Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($tools as $t)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 font-mono text-xs">{{ $t->code }}</td>
                            <td class="px-4 py-2 font-medium">{{ $t->name }}</td>
                            <td class="px-4 py-2 text-center text-gray-600">{{ optional($t->category)->name ?? '—' }}</td>
                            <td class="px-4 py-2 text-center">
                                <span class="text-xs px-2 py-0.5 rounded {{ $t->type === 'durable' ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800' }}">
                                    {{ $t->type }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-center">
                                <span class="{{ $t->isLowStock() ? 'text-red-600 font-bold' : '' }}">{{ $t->qty_available }}</span>
                                <span class="text-gray-400">/{{ $t->qty_total }}</span>
                            </td>
                            <td class="px-4 py-2 text-center text-gray-600">{{ $t->stock_min }}</td>
                            <td class="px-4 py-2 text-center text-gray-600">{{ optional($t->location)->name ?? '—' }}</td>
                            <td class="px-4 py-2 text-center">
                                <span @class([
                                    'text-xs px-2 py-0.5 rounded',
                                    'bg-emerald-100 text-emerald-800' => $t->condition === 'ok',
                                    'bg-amber-100 text-amber-800' => $t->condition === 'danado',
                                    'bg-red-100 text-red-800' => $t->condition === 'scrap',
                                ])>{{ $t->condition }}</span>
                            </td>
                            <td class="px-4 py-2 text-right space-x-2 whitespace-nowrap">
                                <a href="{{ route('tools.label', $t) }}" target="_blank" class="text-emerald-600 hover:underline text-xs">QR</a>
                                <a href="{{ route('tools.show', $t) }}" class="text-indigo-600 hover:underline text-xs">Ver</a>
                                @can('tools.update')
                                    <a href="{{ route('tools.edit', $t) }}" class="text-gray-600 hover:underline text-xs">Editar</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-4 py-10 text-center text-gray-500">Sin herramientas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $tools->links() }}</div>
    </div>
</x-app-layout>
