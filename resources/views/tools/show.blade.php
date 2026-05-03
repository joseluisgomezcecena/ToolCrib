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

        @unless($tool->isSerialized())
            <div class="bg-white rounded-lg shadow p-4 flex items-center gap-6">
                <img src="{{ route('tools.qr', [$tool, 'format' => 'svg', 'size' => 200]) }}" alt="QR {{ $tool->code }}" class="w-40 h-40">
                <div>
                    <div class="text-xs uppercase text-gray-500">Código QR (catálogo / bulk)</div>
                    <div class="font-mono text-xl mt-1">{{ $tool->code }}</div>
                    <div class="text-sm text-gray-600 mt-2">Esta herramienta es bulk: pega el mismo QR en cada unidad si quieres.</div>
                    <div class="mt-3 flex gap-2">
                        <a href="{{ route('tools.label', $tool) }}" target="_blank" class="text-sm px-3 py-1.5 bg-indigo-600 text-white rounded-md">Ver etiqueta</a>
                        <a href="{{ route('tools.qr', [$tool, 'format' => 'png', 'size' => 600]) }}" download="qr-{{ $tool->code }}.png" class="text-sm px-3 py-1.5 border border-gray-300 rounded-md">Descargar PNG</a>
                    </div>
                </div>
            </div>
        @endunless

        @if($tool->isSerialized() && $items)
            <div class="bg-white rounded-lg shadow" x-data="{ editing: null, showAdd: false }">
                <div class="px-4 py-3 border-b flex justify-between items-center">
                    <h3 class="font-semibold">Instancias ({{ $tool->items()->count() }} piezas)</h3>
                    <div class="flex gap-2">
                        <a href="{{ route('items.sheet', $tool) }}" target="_blank" class="text-xs px-3 py-1.5 bg-emerald-600 text-white rounded-md">🖨 Hoja QR de todas</a>
                        @can('tools.update')
                            <button type="button" @click="showAdd = !showAdd" class="text-xs px-3 py-1.5 bg-indigo-600 text-white rounded-md">+ Agregar instancia(s)</button>
                        @endcan
                    </div>
                </div>

                <div x-show="showAdd" x-cloak class="bg-indigo-50 border-b p-4">
                    <form method="POST" action="{{ route('items.store', $tool) }}" class="flex flex-wrap gap-2 items-end">
                        @csrf
                        <div>
                            <label class="block text-xs font-medium">Tag específico (opcional)</label>
                            <input name="tag" placeholder="ej. {{ $tool->code }}-099" class="border rounded-md px-3 py-1.5 text-sm font-mono">
                        </div>
                        <div class="text-gray-400 text-sm">— o bien —</div>
                        <div>
                            <label class="block text-xs font-medium">Cantidad a generar</label>
                            <input type="number" name="count" min="1" max="500" value="1" class="border rounded-md px-3 py-1.5 text-sm w-24">
                            <span class="text-xs text-gray-500">autogenera tags</span>
                        </div>
                        <button class="px-3 py-1.5 bg-indigo-600 text-white rounded-md text-sm">Crear</button>
                    </form>
                </div>

                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-600">
                        <tr>
                            <th class="px-4 py-2 text-left">Tag</th>
                            <th class="px-4 py-2">Estado</th>
                            <th class="px-4 py-2">Condición</th>
                            <th class="px-4 py-2 text-left">Ubicación</th>
                            <th class="px-4 py-2">Próx. mant.</th>
                            <th class="px-4 py-2">Ciclos</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($items as $it)
                            <tr :class="editing === {{ $it->id }} ? 'bg-amber-50' : ''">
                                <td class="px-4 py-2 font-mono text-sm">{{ $it->tag }}</td>
                                <td class="px-4 py-2 text-center">
                                    <span @class([
                                        'text-xs px-2 py-0.5 rounded',
                                        'bg-emerald-100 text-emerald-800' => $it->status === 'available',
                                        'bg-blue-100 text-blue-800' => $it->status === 'in_use',
                                        'bg-amber-100 text-amber-800' => $it->status === 'maintenance',
                                        'bg-gray-200 text-gray-800' => $it->status === 'lost',
                                        'bg-red-100 text-red-800' => $it->status === 'scrapped',
                                    ])>{{ $it->status }}</span>
                                </td>
                                <td class="px-4 py-2 text-center">{{ $it->condition }}</td>
                                <td class="px-4 py-2 text-gray-700">{{ optional($it->location)->name ?? '—' }}</td>
                                <td class="px-4 py-2 text-center {{ $it->isMaintenanceDue() ? 'text-red-600 font-bold' : '' }}">{{ $it->next_maintenance_at?->format('d/M/Y') ?? '—' }}</td>
                                <td class="px-4 py-2 text-center">{{ $it->used_cycles }}{{ $tool->life_cycles ? ' / '.$tool->life_cycles : '' }}</td>
                                <td class="px-4 py-2 text-right whitespace-nowrap space-x-1">
                                    <a href="{{ route('items.label', [$tool, $it]) }}" target="_blank" class="text-emerald-600 hover:underline text-xs">QR</a>
                                    @can('tools.update')
                                        <button type="button" @click="editing = (editing === {{ $it->id }} ? null : {{ $it->id }})" class="text-gray-600 hover:underline text-xs">Editar</button>
                                    @endcan
                                </td>
                            </tr>
                            <tr x-show="editing === {{ $it->id }}" x-cloak>
                                <td colspan="7" class="bg-amber-50 p-4">
                                    <form method="POST" action="{{ route('items.update', [$tool, $it]) }}" class="grid grid-cols-1 md:grid-cols-5 gap-3">
                                        @csrf @method('PUT')
                                        <div><label class="block text-xs">Tag</label>
                                            <input name="tag" value="{{ $it->tag }}" class="w-full border rounded px-2 py-1 text-sm font-mono"></div>
                                        <div><label class="block text-xs">Estado</label>
                                            <select name="status" class="w-full border rounded px-2 py-1 text-sm">
                                                @foreach(['available','in_use','maintenance','lost','scrapped'] as $s)
                                                    <option value="{{ $s }}" @selected($it->status===$s)>{{ $s }}</option>
                                                @endforeach
                                            </select></div>
                                        <div><label class="block text-xs">Condición</label>
                                            <select name="condition" class="w-full border rounded px-2 py-1 text-sm">
                                                @foreach(['ok','danado','scrap'] as $c)
                                                    <option value="{{ $c }}" @selected($it->condition===$c)>{{ $c }}</option>
                                                @endforeach
                                            </select></div>
                                        <div><label class="block text-xs">Ubicación</label>
                                            <select name="location_id" class="w-full border rounded px-2 py-1 text-sm">
                                                <option value="">—</option>
                                                @foreach($locations as $l)
                                                    <option value="{{ $l->id }}" @selected($it->location_id==$l->id)>{{ $l->name }}</option>
                                                @endforeach
                                            </select></div>
                                        <div><label class="block text-xs">Próx. mant.</label>
                                            <input type="date" name="next_maintenance_at" value="{{ $it->next_maintenance_at?->toDateString() }}" class="w-full border rounded px-2 py-1 text-sm"></div>
                                        <div class="md:col-span-5 flex justify-end gap-2">
                                            <button type="button" @click="editing = null" class="px-3 py-1 text-sm border rounded">Cancelar</button>
                                            <button class="px-3 py-1 text-sm bg-indigo-600 text-white rounded">Guardar</button>
                                        </div>
                                    </form>
                                    @can('tools.delete')
                                        <form method="POST" action="{{ route('items.destroy', [$tool, $it]) }}"
                                              onsubmit="return confirm('¿Eliminar instancia {{ $it->tag }}?')" class="mt-2 text-right">
                                            @csrf @method('DELETE')
                                            <button class="px-3 py-1 text-sm bg-red-600 text-white rounded">Eliminar instancia</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">Sin instancias. Genera algunas arriba.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="border-t">
                    @include('partials.pagination', ['paginator' => $items])
                </div>
            </div>
        @endif

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
                        <th class="px-4 py-2 text-left">Orden / Destino</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($movements as $m)
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
                            <td class="px-4 py-1.5">{{ $m->work_order ?? '—' }} / {{ optional($m->toLocation)->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Sin movimientos.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="border-t">
                @include('partials.pagination', ['paginator' => $movements])
            </div>
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
