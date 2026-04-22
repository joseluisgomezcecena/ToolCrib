<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Programar mantenimiento</h2></x-slot>

    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow p-6">
            <form method="POST" action="{{ route('maintenances.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium">Herramienta *</label>
                    <select name="tool_id" required class="mt-1 w-full border rounded-md px-3 py-2">
                        @foreach($tools as $t)
                            <option value="{{ $t->id }}">{{ $t->code }} — {{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium">Tipo *</label>
                    <select name="type" required class="mt-1 w-full border rounded-md px-3 py-2">
                        <option value="preventivo">Preventivo</option>
                        <option value="calibracion">Calibración</option>
                        <option value="correctivo">Correctivo</option>
                        <option value="inspeccion">Inspección</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium">Fecha programada *</label>
                    <input type="date" name="scheduled_at" required class="mt-1 w-full border rounded-md px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium">Notas</label>
                    <textarea name="notes" rows="3" class="mt-1 w-full border rounded-md px-3 py-2"></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <a href="{{ route('maintenances.index') }}" class="px-4 py-2 text-sm border rounded-md">Cancelar</a>
                    <button class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-md">Programar</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
