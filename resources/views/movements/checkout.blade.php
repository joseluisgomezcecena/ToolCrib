<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Nueva salida / consumo</h2></x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow p-6">
            <form method="POST" action="{{ route('movements.checkout.store') }}">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium">Código de herramienta *</label>
                        <input name="tool_code" autofocus list="tool-list" class="mt-1 w-full border rounded-md px-3 py-2 font-mono" required />
                        <datalist id="tool-list">
                            @foreach($tools as $t)
                                <option value="{{ $t->code }}">{{ $t->name }}</option>
                            @endforeach
                        </datalist>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Gafete del cliente</label>
                        <input name="customer_code" list="cust-list" class="mt-1 w-full border rounded-md px-3 py-2 font-mono" />
                        <datalist id="cust-list">
                            @foreach($customers as $c)
                                <option value="{{ $c->employee_code }}">{{ $c->name }}</option>
                            @endforeach
                        </datalist>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Cantidad *</label>
                        <input type="number" min="1" name="qty" value="1" class="mt-1 w-full border rounded-md px-3 py-2" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Orden de producción</label>
                        <input name="work_order" class="mt-1 w-full border rounded-md px-3 py-2" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium">Ubicación destino *</label>
                        <select name="to_location_id" required class="mt-1 w-full border rounded-md px-3 py-2">
                            <option value="">— selecciona a qué máquina/línea va —</option>
                            @foreach($locations as $l)
                                <option value="{{ $l->id }}">{{ $l->name }} ({{ $l->type }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Devolver antes de</label>
                        <input type="datetime-local" name="return_due_at" value="{{ now()->addHours(8)->format('Y-m-d\TH:i') }}" class="mt-1 w-full border rounded-md px-3 py-2" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium">Notas</label>
                        <textarea name="notes" rows="2" class="mt-1 w-full border rounded-md px-3 py-2"></textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-6">
                    <a href="{{ route('movements.index') }}" class="px-4 py-2 text-sm border rounded-md">Cancelar</a>
                    <button class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Registrar</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
