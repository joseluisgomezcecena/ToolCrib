<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800">Compras / Recepciones</h2>
            <a href="{{ route('purchases.create') }}" class="bg-emerald-600 text-white px-4 py-2 rounded-md text-sm hover:bg-emerald-700">+ Nueva compra</a>
        </div>
    </x-slot>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        <form method="GET" x-data class="bg-white p-3 rounded-md shadow-sm flex flex-wrap gap-2 items-end">
            <div>
                <label class="text-xs text-gray-600">Orden de compra</label>
                <input name="po" value="{{ request('po') }}" placeholder="OC-..."
                       class="border rounded-md px-3 py-1.5 text-sm"
                       @input.debounce.800ms="$el.form.requestSubmit()"
                       @keydown.enter.prevent="$el.form.requestSubmit()"
                       @if(request('po')) autofocus @endif>
            </div>
            <div>
                <label class="text-xs text-gray-600">Herramienta</label>
                <select name="tool_id" class="border rounded-md px-3 py-1.5 text-sm" @change="$el.form.requestSubmit()">
                    <option value="">Todas</option>
                    @foreach($tools as $t)
                        <option value="{{ $t->id }}" @selected(request('tool_id') == $t->id)>{{ $t->code }} — {{ $t->name }}</option>
                    @endforeach
                </select>
            </div>
            <button class="bg-gray-800 text-white px-3 py-1.5 rounded-md text-sm">Filtrar</button>
        </form>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-600">
                    <tr>
                        <th class="px-4 py-2 text-left">Fecha</th>
                        <th class="px-4 py-2 text-left">Herramienta</th>
                        <th class="px-4 py-2">Cantidad</th>
                        <th class="px-4 py-2 text-left">OC</th>
                        <th class="px-4 py-2 text-left">Operador</th>
                        <th class="px-4 py-2 text-left">Notas</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($purchases as $p)
                        <tr>
                            <td class="px-4 py-2">{{ $p->occurred_at->format('d/M/Y H:i') }}</td>
                            <td class="px-4 py-2">
                                <a href="{{ route('tools.show', $p->tool) }}" class="text-indigo-600 hover:underline">
                                    <div class="font-medium">{{ $p->tool->name ?? '—' }}</div>
                                    <div class="text-xs font-mono text-gray-500">{{ $p->tool->code ?? '' }}</div>
                                </a>
                            </td>
                            <td class="px-4 py-2 text-center font-bold text-emerald-700">+{{ $p->qty }}</td>
                            <td class="px-4 py-2 font-mono text-xs">{{ $p->work_order ?? '—' }}</td>
                            <td class="px-4 py-2">{{ optional($p->operator)->name ?? '—' }}</td>
                            <td class="px-4 py-2 text-xs text-gray-600">{{ \Illuminate\Support\Str::limit($p->notes, 60) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-gray-500">Sin compras registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="border-t">
                @include('partials.pagination', ['paginator' => $purchases])
            </div>
        </div>
    </div>
</x-app-layout>
