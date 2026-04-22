<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl">Mantenimientos</h2>
            @can('maintenances.manage')
                <a href="{{ route('maintenances.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm">+ Programar</a>
            @endcan
        </div>
    </x-slot>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-600">
                    <tr>
                        <th class="px-4 py-2 text-left">Herramienta</th>
                        <th class="px-4 py-2">Tipo</th>
                        <th class="px-4 py-2">Programado</th>
                        <th class="px-4 py-2">Estado</th>
                        <th class="px-4 py-2">Completado</th>
                        <th class="px-4 py-2">Costo</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($maintenances as $m)
                        <tr>
                            <td class="px-4 py-2">
                                <div class="font-medium">{{ $m->tool->name ?? '—' }}</div>
                                <div class="text-xs font-mono text-gray-500">{{ $m->tool->code ?? '' }}</div>
                            </td>
                            <td class="px-4 py-2 text-center">{{ $m->type }}</td>
                            <td class="px-4 py-2 text-center {{ $m->scheduled_at->isPast() && $m->status !== 'completado' ? 'text-red-600 font-bold' : '' }}">
                                {{ $m->scheduled_at->format('d/M/Y') }}
                            </td>
                            <td class="px-4 py-2 text-center">{{ $m->status }}</td>
                            <td class="px-4 py-2 text-center">{{ $m->completed_at?->format('d/M/Y') ?? '—' }}</td>
                            <td class="px-4 py-2 text-center">{{ $m->cost ? '$'.number_format($m->cost, 2) : '—' }}</td>
                            <td class="px-4 py-2 text-right">
                                @can('maintenances.manage')
                                    @if($m->status !== 'completado')
                                        <form method="POST" action="{{ route('maintenances.complete', $m) }}" class="inline">
                                            @csrf
                                            <input type="number" name="cost" placeholder="Costo" step="0.01" class="w-20 text-xs border rounded px-2 py-1">
                                            <button class="text-emerald-600 text-xs hover:underline ml-1">Completar</button>
                                        </form>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-10 text-center text-gray-500">Sin mantenimientos programados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $maintenances->links() }}</div>
    </div>
</x-app-layout>
