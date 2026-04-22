@csrf
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium">Código (barcode / RFID) *</label>
        <input name="code" value="{{ old('code', $tool->code ?? '') }}" class="mt-1 w-full border rounded-md px-3 py-2" required />
    </div>
    <div>
        <label class="block text-sm font-medium">Nombre *</label>
        <input name="name" value="{{ old('name', $tool->name ?? '') }}" class="mt-1 w-full border rounded-md px-3 py-2" required />
    </div>
    <div class="md:col-span-2">
        <label class="block text-sm font-medium">Descripción</label>
        <textarea name="description" rows="2" class="mt-1 w-full border rounded-md px-3 py-2">{{ old('description', $tool->description ?? '') }}</textarea>
    </div>
    <div>
        <label class="block text-sm font-medium">Categoría</label>
        <select name="category_id" class="mt-1 w-full border rounded-md px-3 py-2">
            <option value="">—</option>
            @foreach($categories as $c)
                <option value="{{ $c->id }}" @selected(old('category_id', $tool->category_id ?? '') == $c->id)>{{ $c->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium">Ubicación</label>
        <select name="location_id" class="mt-1 w-full border rounded-md px-3 py-2">
            <option value="">—</option>
            @foreach($locations as $l)
                <option value="{{ $l->id }}" @selected(old('location_id', $tool->location_id ?? '') == $l->id)>{{ $l->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium">Tipo *</label>
        <select name="type" class="mt-1 w-full border rounded-md px-3 py-2" required>
            <option value="durable" @selected(old('type', $tool->type ?? 'durable') === 'durable')>Durable (regresa)</option>
            <option value="consumible" @selected(old('type', $tool->type ?? '') === 'consumible')>Consumible (no regresa)</option>
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium">Condición *</label>
        <select name="condition" class="mt-1 w-full border rounded-md px-3 py-2" required>
            <option value="ok" @selected(old('condition', $tool->condition ?? 'ok') === 'ok')>OK</option>
            <option value="danado" @selected(old('condition', $tool->condition ?? '') === 'danado')>Dañado</option>
            <option value="scrap" @selected(old('condition', $tool->condition ?? '') === 'scrap')>Scrap</option>
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium">Qty total *</label>
        <input type="number" min="0" name="qty_total" value="{{ old('qty_total', $tool->qty_total ?? 1) }}" class="mt-1 w-full border rounded-md px-3 py-2" required />
    </div>
    <div>
        <label class="block text-sm font-medium">Qty disponible *</label>
        <input type="number" min="0" name="qty_available" value="{{ old('qty_available', $tool->qty_available ?? 1) }}" class="mt-1 w-full border rounded-md px-3 py-2" required />
    </div>
    <div>
        <label class="block text-sm font-medium">Stock mínimo *</label>
        <input type="number" min="0" name="stock_min" value="{{ old('stock_min', $tool->stock_min ?? 0) }}" class="mt-1 w-full border rounded-md px-3 py-2" required />
    </div>
    <div>
        <label class="block text-sm font-medium">Stock máximo</label>
        <input type="number" min="0" name="stock_max" value="{{ old('stock_max', $tool->stock_max ?? '') }}" class="mt-1 w-full border rounded-md px-3 py-2" />
    </div>
    <div>
        <label class="block text-sm font-medium">Costo unitario</label>
        <input type="number" min="0" step="0.01" name="unit_cost" value="{{ old('unit_cost', $tool->unit_cost ?? '') }}" class="mt-1 w-full border rounded-md px-3 py-2" />
    </div>
    <div>
        <label class="block text-sm font-medium">Vida útil (ciclos)</label>
        <input type="number" min="0" name="life_cycles" value="{{ old('life_cycles', $tool->life_cycles ?? '') }}" class="mt-1 w-full border rounded-md px-3 py-2" />
    </div>
    <div>
        <label class="block text-sm font-medium">Próximo mantenimiento</label>
        <input type="date" name="next_maintenance_at" value="{{ old('next_maintenance_at', optional($tool->next_maintenance_at ?? null)->toDateString()) }}" class="mt-1 w-full border rounded-md px-3 py-2" />
    </div>
    <label class="inline-flex items-center gap-2 mt-2">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $tool->is_active ?? true))>
        <span class="text-sm">Activa</span>
    </label>
</div>

<div class="flex justify-end gap-2 mt-6">
    <a href="{{ route('tools.index') }}" class="px-4 py-2 text-sm border rounded-md">Cancelar</a>
    <button class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Guardar</button>
</div>
