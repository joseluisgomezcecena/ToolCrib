<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            @php
                $cards = [
                    ['Herramientas', $stats['tools_total'], 'text-indigo-600'],
                    ['Disponibles',  $stats['tools_available'], 'text-emerald-600'],
                    ['Stock bajo',   $stats['tools_low_stock'], 'text-amber-600'],
                    ['Salidas abiertas', $stats['open_checkouts'], 'text-blue-600'],
                    ['Vencidas',     $stats['overdue'], 'text-red-600'],
                    ['Alertas',      $stats['alerts_unresolved'], 'text-fuchsia-600'],
                ];
            @endphp
            @foreach($cards as [$label, $value, $color])
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="text-xs uppercase text-gray-500">{{ $label }}</div>
                    <div class="text-3xl font-bold {{ $color }} mt-1">{{ $value }}</div>
                </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-lg shadow">
                <div class="px-4 py-3 border-b flex justify-between items-center">
                    <h3 class="font-semibold">Alertas recientes</h3>
                    <a href="{{ route('alerts.index') }}" class="text-sm text-indigo-600 hover:underline">Ver todas</a>
                </div>
                <ul class="divide-y">
                    @forelse($alerts as $a)
                        <li class="px-4 py-3">
                            <div class="flex items-start gap-3">
                                <span @class([
                                    'inline-block w-2 h-2 rounded-full mt-1.5',
                                    'bg-red-500' => $a->severity === 'critical',
                                    'bg-amber-500' => $a->severity === 'warning',
                                    'bg-blue-500' => $a->severity === 'info',
                                ])></span>
                                <div class="flex-1">
                                    <div class="text-sm font-medium">{{ $a->title }}</div>
                                    <div class="text-sm text-gray-600">{{ $a->message }}</div>
                                    <div class="text-xs text-gray-400 mt-0.5">{{ $a->created_at->diffForHumans() }}</div>
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="px-4 py-8 text-center text-gray-500">Sin alertas abiertas.</li>
                    @endforelse
                </ul>
            </div>

            <div class="bg-white rounded-lg shadow">
                <div class="px-4 py-3 border-b">
                    <h3 class="font-semibold">Últimos movimientos</h3>
                </div>
                <ul class="divide-y">
                    @forelse($recent as $m)
                        <li class="px-4 py-3 text-sm">
                            <div class="flex justify-between">
                                <span class="font-medium">{{ $m->tool->name ?? '—' }}</span>
                                <span @class([
                                    'text-xs px-2 py-0.5 rounded',
                                    'bg-blue-100 text-blue-800' => $m->type === 'checkout',
                                    'bg-emerald-100 text-emerald-800' => $m->type === 'checkin',
                                    'bg-orange-100 text-orange-800' => $m->type === 'consume',
                                    'bg-gray-100 text-gray-800' => in_array($m->type, ['transfer','scrap']),
                                ])>{{ strtoupper($m->type) }}</span>
                            </div>
                            <div class="text-xs text-gray-500 mt-0.5">
                                {{ optional($m->customer)->name ?? '—' }} · {{ $m->qty }}u · {{ $m->occurred_at->format('d/M H:i') }}
                            </div>
                        </li>
                    @empty
                        <li class="px-4 py-8 text-center text-gray-500">Aún no hay movimientos.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
