<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Mis herramientas</h2></x-slot>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-2 text-left">Fecha</th>
                        <th class="px-4 py-2 text-left">Herramienta</th>
                        <th class="px-4 py-2">Qty</th>
                        <th class="px-4 py-2">Tipo</th>
                        <th class="px-4 py-2">Vence</th>
                        <th class="px-4 py-2">Devuelto</th>
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
                            <td class="px-4 py-2 text-center">{{ $m->qty }}</td>
                            <td class="px-4 py-2 text-center">{{ $m->type }}</td>
                            <td class="px-4 py-2 text-center">{{ $m->return_due_at?->format('d/M H:i') ?? '—' }}</td>
                            <td class="px-4 py-2 text-center">
                                @if($m->returned_at)
                                    <span class="text-emerald-600">{{ $m->returned_at->format('d/M H:i') }}</span>
                                @else
                                    <span class="text-red-600">Pendiente</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-gray-500">Aún no tienes movimientos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">@include('partials.pagination', ['paginator' => $movements])</div>
    </div>
</x-app-layout>
