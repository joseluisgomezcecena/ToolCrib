<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl">{{ $tool->name }} <span class="text-gray-500 text-sm font-mono">{{ $tool->code }}</span></h2>
            <div class="flex gap-2">
                <a href="{{ route('tools.label', $tool) }}" target="_blank" class="bg-emerald-600 text-white px-3 py-1.5 rounded-md text-sm hover:bg-emerald-700">🖨 Etiqueta QR</a>
                @can('tools.update')
                    <a href="{{ route('tools.edit', $tool) }}" class="bg-gray-800 text-white px-3 py-1.5 rounded-md text-sm">Editar</a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6" x-data="{ modal: null }">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-xs uppercase text-gray-500">Disponible / Total</div>
                <div class="text-3xl font-bold mt-1">{{ $tool->qty_available }} / {{ $tool->qty_total }}</div>
                <div class="text-xs text-gray-500 mt-1">Mínimo {{ $tool->stock_min }}</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-xs uppercase text-gray-500">Tipo / Condición</div>
                <div class="text-xl font-semibold mt-1">{{ $tool->type }} · {{ $tool->condition }}</div>
                <div class="text-xs text-gray-500 mt-1">Ubicación: {{ optional($tool->location)->name ?? '—' }}</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-xs uppercase text-gray-500">Mantenimiento</div>
                <div class="text-sm mt-1">
                    Último: {{ $tool->last_maintenance_at?->format('d/M/Y') ?? '—' }}<br>
                    Próximo: <span class="{{ $tool->isMaintenanceDue() ? 'text-red-600 font-bold' : '' }}">{{ $tool->next_maintenance_at?->format('d/M/Y') ?? '—' }}</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-4 flex flex-wrap gap-2 items-center">
            <span class="text-sm text-gray-600 mr-2">Acciones:</span>
            @can('tools.update')
                <button type="button" @click="modal = 'receipt'" class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-md text-sm">➕ Recibir compra</button>
            @endcan
            @can('movements.transfer')
                <button type="button" @click="modal = 'transfer'" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-md text-sm">🔄 Transferir</button>
            @endcan
            <button type="button" @click="modal = 'scrap'" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded-md text-sm">🗑️ Scrap</button>
        </div>

        <div class="bg-white rounded-lg shadow p-4 flex items-center gap-6">
            <img src="{{ route('tools.qr', [$tool, 'format' => 'svg', 'size' => 200]) }}" alt="QR {{ $tool->code }}" class="w-40 h-40">
            <div>
                <div class="text-xs uppercase text-gray-500">Código QR</div>
                <div class="font-mono text-xl mt-1">{{ $tool->code }}</div>
                <div class="text-sm text-gray-600 mt-2">Escanea con el kiosko o celular. Imprime la etiqueta y pégala en la herramienta.</div>
                <div class="mt-3 flex gap-2">
                    <a href="{{ route('tools.label', $tool) }}" target="_blank" class="text-sm px-3 py-1.5 bg-indigo-600 text-white rounded-md">Ver etiqueta</a>
                    <a href="{{ route('tools.qr', [$tool, 'format' => 'png', 'size' => 600]) }}" download="qr-{{ $tool->code }}.png" class="text-sm px-3 py-1.5 border border-gray-300 rounded-md">Descargar PNG</a>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow">
            <div class="px-4 py-3 border-b"><h3 class="font-semibold">Historial de movimientos</h3></div>
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-600 uppercase">
                    <tr>
                        <th class="px-4 py-2 text-left">Fecha</th>
                        <th class="px-4 py-2">Tipo</th>
                        <th class="px-4 py-2">Qty</th>
                        <th class="px-4 py-2 text-left">Cliente</th>
                        <th class="px-4 py-2 text-left">Operador</th>
                        <th class="px-4 py-2 text-left">Orden / Máquina</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($tool->movements()->latest('occurred_at')->limit(50)->get() as $m)
                        <tr>
                            <td class="px-4 py-1.5">{{ $m->occurred_at->format('d/M/Y H:i') }}</td>
                            <td class="px-4 py-1.5 text-center">
                                <span @class([
                                    'text-xs px-2 py-0.5 rounded',
                                    'bg-blue-100 text-blue-800' => $m->type === 'checkout',
                                    'bg-emerald-100 text-emerald-800' => in_array($m->type, ['checkin','receipt']),
                                    'bg-orange-100 text-orange-800' => $m->type === 'consume',
                                    'bg-red-100 text-red-800' => $m->type === 'scrap',
                                    'bg-indigo-100 text-indigo-800' => $m->type === 'transfer',
                                ])>{{ $m->type }}</span>
                            </td>
                            <td class="px-4 py-1.5 text-center">{{ $m->qty }}</td>
                            <td class="px-4 py-1.5">{{ optional($m->customer)->name ?? '—' }}</td>
                            <td class="px-4 py-1.5">{{ optional($m->operator)->name ?? '—' }}</td>
                            <td class="px-4 py-1.5">{{ $m->work_order }} / {{ $m->machine }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Sin movimientos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Modal: Recepción --}}
        <div x-show="modal === 'receipt'" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-40" @keydown.escape.window="modal = null">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6" @click.outside="modal = null">
                <h3 class="text-lg font-semibold mb-1">Recibir compra</h3>
                <p class="text-sm text-gray-600 mb-4">Aumenta el stock (ej. llegada de orden de compra).</p>
                <form method="POST" action="{{ route('tools.receipt', $tool) }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium">Cantidad recibida *</label>
                        <input type="number" name="qty" min="1" required class="mt-1 w-full border rounded-md px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Orden de compra</label>
                        <input type="text" name="purchase_order" placeholder="OC-2025-0123" class="mt-1 w-full border rounded-md px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Costo unitario (opcional, actualiza el actual)</label>
                        <input type="number" step="0.01" min="0" name="unit_cost" class="mt-1 w-full border rounded-md px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Notas</label>
                        <textarea name="notes" rows="2" class="mt-1 w-full border rounded-md px-3 py-2"></textarea>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="modal = null" class="px-4 py-2 text-sm border rounded-md">Cancelar</button>
                        <button class="px-4 py-2 text-sm bg-emerald-600 text-white rounded-md">Registrar ingreso</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal: Transferir --}}
        <div x-show="modal === 'transfer'" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-40" @keydown.escape.window="modal = null">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6" @click.outside="modal = null">
                <h3 class="text-lg font-semibold mb-1">Transferir ubicación</h3>
                <p class="text-sm text-gray-600 mb-4">Mueve la herramienta a otra ubicación sin asignarla a un usuario.</p>
                <form method="POST" action="{{ route('tools.transfer', $tool) }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium">De</label>
                        <div class="mt-1 text-sm text-gray-700">{{ optional($tool->location)->name ?? '— (sin ubicación)' }}</div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Destino *</label>
                        <select name="to_location_id" required class="mt-1 w-full border rounded-md px-3 py-2">
                            <option value="">—</option>
                            @foreach($locations as $l)
                                <option value="{{ $l->id }}" @if($l->id === $tool->location_id) disabled @endif>{{ $l->name }} ({{ $l->type }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Cantidad (opcional)</label>
                        <input type="number" name="qty" min="1" max="{{ $tool->qty_available }}" placeholder="Todas ({{ $tool->qty_available }})" class="mt-1 w-full border rounded-md px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Notas</label>
                        <textarea name="notes" rows="2" class="mt-1 w-full border rounded-md px-3 py-2"></textarea>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="modal = null" class="px-4 py-2 text-sm border rounded-md">Cancelar</button>
                        <button class="px-4 py-2 text-sm bg-blue-600 text-white rounded-md">Transferir</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal: Scrap --}}
        <div x-show="modal === 'scrap'" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-40" @keydown.escape.window="modal = null">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6" @click.outside="modal = null">
                <h3 class="text-lg font-semibold mb-1 text-red-700">Marcar como scrap</h3>
                <p class="text-sm text-gray-600 mb-4">La herramienta sale del inventario <b>definitivamente</b>. No se puede deshacer.</p>
                <form method="POST" action="{{ route('tools.scrap', $tool) }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium">Cantidad a dar de baja *</label>
                        <input type="number" name="qty" min="1" max="{{ $tool->qty_available }}" required class="mt-1 w-full border rounded-md px-3 py-2">
                        <div class="text-xs text-gray-500 mt-1">Disponibles: {{ $tool->qty_available }}</div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Motivo *</label>
                        <textarea name="notes" rows="3" required placeholder="Ej. Se quebró durante uso en máquina CNC-01" class="mt-1 w-full border rounded-md px-3 py-2"></textarea>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="modal = null" class="px-4 py-2 text-sm border rounded-md">Cancelar</button>
                        <button class="px-4 py-2 text-sm bg-red-600 text-white rounded-md">Registrar scrap</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
