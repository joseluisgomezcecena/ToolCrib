<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Registrar compra / recepción</h2></x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow p-6"
             x-data="{
                tools: {{ $tools->map(fn($t) => ['id' => $t->id, 'code' => $t->code, 'name' => $t->name, 'tracking_mode' => $t->tracking_mode, 'qty_total' => $t->qty_total, 'qty_available' => $t->qty_available, 'unit_cost' => $t->unit_cost])->toJson() }},
                toolId: '{{ old('tool_id') }}',
                qty: {{ old('qty', 1) }},
                get tool() { return this.tools.find(t => t.id == this.toolId); }
             }">
            <form method="POST" action="{{ route('purchases.store') }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-medium">Herramienta *</label>
                    <select name="tool_id" x-model="toolId" required class="mt-1 w-full border rounded-md px-3 py-2">
                        <option value="">— Selecciona una herramienta —</option>
                        @foreach($tools as $t)
                            <option value="{{ $t->id }}">
                                {{ $t->code }} — {{ $t->name }}
                                ({{ $t->tracking_mode }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <template x-if="tool">
                    <div class="bg-indigo-50 border border-indigo-200 rounded-md p-3 text-sm">
                        <div class="grid grid-cols-2 gap-2">
                            <div><span class="text-gray-500">Stock actual:</span> <b x-text="tool.qty_available"></b> / <span x-text="tool.qty_total"></span></div>
                            <div><span class="text-gray-500">Modo:</span> <b x-text="tool.tracking_mode"></b></div>
                        </div>
                        <template x-if="tool.tracking_mode === 'serialized'">
                            <div class="mt-2 text-xs text-indigo-700">
                                ⚠ Serializada: se generarán <b x-text="qty"></b> instancias nuevas con tags secuenciales (ej. <span x-text="tool.code"></span>-XXX) a partir de la última.
                            </div>
                        </template>
                    </div>
                </template>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium">Cantidad recibida *</label>
                        <input type="number" name="qty" x-model.number="qty" min="1" value="{{ old('qty', 1) }}" required class="mt-1 w-full border rounded-md px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Orden de compra</label>
                        <input type="text" name="purchase_order" value="{{ old('purchase_order') }}" placeholder="OC-2026-0042" class="mt-1 w-full border rounded-md px-3 py-2 font-mono">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Costo unitario (opcional)</label>
                        <input type="number" step="0.01" min="0" name="unit_cost" value="{{ old('unit_cost') }}" placeholder="—" class="mt-1 w-full border rounded-md px-3 py-2">
                        <div class="text-xs text-gray-500 mt-1">Si lo capturas, actualiza el costo de referencia de la herramienta.</div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Proveedor / notas</label>
                        <input type="text" name="notes" value="{{ old('notes') }}" placeholder="Proveedor X, factura 12345" class="mt-1 w-full border rounded-md px-3 py-2">
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-4 border-t">
                    <a href="{{ route('purchases.index') }}" class="px-4 py-2 text-sm border rounded-md">Cancelar</a>
                    <button class="px-4 py-2 text-sm bg-emerald-600 text-white rounded-md hover:bg-emerald-700">Registrar compra</button>
                </div>
            </form>
        </div>

        <div class="mt-4 text-xs text-gray-500">
            <b>Tip:</b> si recibes una OC con varios SKUs, captura cada SKU como una compra separada usando el mismo número de OC.
            Después puedes filtrar por OC en el listado para ver todo lo que llegó en ese embarque.
        </div>
    </div>
</x-app-layout>
