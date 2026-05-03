<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Ubicaciones</h2></x-slot>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="md:col-span-1">
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="font-semibold mb-2">Nueva ubicación</h3>
                <form method="POST" action="{{ route('locations.store') }}" class="space-y-2">
                    @csrf
                    <input name="name" placeholder="Nombre (ej. Línea A)" class="w-full border rounded-md px-3 py-2" required>
                    <input name="code" placeholder="Código" class="w-full border rounded-md px-3 py-2 font-mono" required>
                    <select name="type" class="w-full border rounded-md px-3 py-2">
                        <option value="almacen">Almacén</option>
                        <option value="area">Área</option>
                        <option value="linea">Línea</option>
                        <option value="maquina">Máquina</option>
                        <option value="kit">Kit</option>
                    </select>
                    <select name="parent_id" class="w-full border rounded-md px-3 py-2">
                        <option value="">Sin padre</option>
                        @foreach($all as $l)
                            <option value="{{ $l->id }}">{{ $l->name }}</option>
                        @endforeach
                    </select>
                    <button class="w-full bg-indigo-600 text-white rounded-md py-2 text-sm">Crear</button>
                </form>
            </div>
        </div>

        <div class="md:col-span-2">
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-600">
                        <tr>
                            <th class="px-4 py-2 text-left">Código</th>
                            <th class="px-4 py-2 text-left">Nombre</th>
                            <th class="px-4 py-2">Tipo</th>
                            <th class="px-4 py-2">Padre</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach($locations as $l)
                            <tr>
                                <td class="px-4 py-2 font-mono text-xs">{{ $l->code }}</td>
                                <td class="px-4 py-2 font-medium">{{ $l->name }}</td>
                                <td class="px-4 py-2 text-center text-gray-600">{{ $l->type }}</td>
                                <td class="px-4 py-2 text-center text-gray-600">{{ optional($l->parent)->name ?? '—' }}</td>
                                <td class="px-4 py-2 text-right">
                                    <form method="POST" action="{{ route('locations.destroy', $l) }}" onsubmit="return confirm('¿Eliminar?')" class="inline">
                                        @csrf @method('DELETE')
                                        <button class="text-red-600 text-xs hover:underline">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">@include('partials.pagination', ['paginator' => $locations])</div>
        </div>
    </div>
</x-app-layout>
