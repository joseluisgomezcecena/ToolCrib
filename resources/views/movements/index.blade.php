<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl">Movimientos</h2>
            @can('movements.checkout')
                <a href="{{ route('movements.checkout') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700">+ Salida (check-out)</a>
            @endcan
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        <form method="GET" class="bg-white p-3 rounded-md shadow-sm flex flex-wrap gap-2 items-end">
            <input name="q" value="{{ request('q') }}" placeholder="Código o nombre" class="border rounded-md px-3 py-1.5 text-sm" />
            <select name="type" class="border rounded-md px-3 py-1.5 text-sm">
                <option value="">Todos</option>
                @foreach(['checkout','checkin','consume','transfer','scrap','receipt'] as $t)
                    <option value="{{ $t }}" @selected(request('type')===$t)>{{ $t }}</option>
                @endforeach
            </select>
            <label class="inline-flex items-center text-sm gap-1"><input type="checkbox" name="open" value="1" @checked(request('open'))> Solo abiertas</label>
            <button class="bg-gray-800 text-white px-3 py-1.5 rounded-md text-sm">Filtrar</button>
        </form>

        <div class="bg-white rounded-lg shadow overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-2 text-left">Fecha</th>
                        <th class="px-4 py-2 text-left">Herramienta</th>
                        <th class="px-4 py-2">Tipo</th>
                        <th class="px-4 py-2">Qty</th>
                        <th class="px-4 py-2 text-left">Cliente</th>
                        <th class="px-4 py-2 text-left">Operador</th>
                        <th class="px-4 py-2">Vence</th>
                        <th class="px-4 py-2">Devuelto</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($movements as $m)
                        <tr class="{{ $m->isOverdue() ? 'bg-red-50' : '' }}">
                            <td class="px-4 py-2">{{ $m->occurred_at->format('d/M H:i') }}</td>
                            <td class="px-4 py-2">
                                <div class="font-medium">{{ $m->tool->name ?? '—' }}</div>
                                <div class="text-xs font-mono text-gray-500">{{ $m->tool->code ?? '' }}</div>
                            </td>
                            <td class="px-4 py-2 text-center">{{ $m->type }}</td>
                            <td class="px-4 py-2 text-center">{{ $m->qty }}</td>
                            <td class="px-4 py-2">{{ optional($m->customer)->name ?? '—' }}</td>
                            <td class="px-4 py-2">{{ optional($m->operator)->name ?? '—' }}</td>
                            <td class="px-4 py-2 text-center">{{ $m->return_due_at?->format('d/M H:i') ?? '—' }}</td>
                            <td class="px-4 py-2 text-center">{{ $m->returned_at?->format('d/M H:i') ?? '—' }}</td>
                            <td class="px-4 py-2 text-right">
                                @if($m->type === 'checkout' && !$m->returned_at)
                                    @can('movements.checkin')
                                        <form method="POST" action="{{ route('movements.checkin', $m) }}" class="inline">
                                            @csrf
                                            <button class="text-emerald-600 hover:underline text-xs">Devolver</button>
                                        </form>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-4 py-10 text-center text-gray-500">Sin movimientos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.pagination', ['paginator' => $movements])
    </div>
</x-app-layout>
