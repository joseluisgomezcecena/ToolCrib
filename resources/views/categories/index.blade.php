<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Categorías</h2></x-slot>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="md:col-span-1">
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="font-semibold mb-2">Nueva categoría</h3>
                <form method="POST" action="{{ route('categories.store') }}" class="space-y-2">
                    @csrf
                    <input name="name" placeholder="Nombre" class="w-full border rounded-md px-3 py-2" required>
                    <textarea name="description" placeholder="Descripción" class="w-full border rounded-md px-3 py-2" rows="2"></textarea>
                    <button class="w-full bg-indigo-600 text-white rounded-md py-2 text-sm">Crear</button>
                </form>
            </div>
        </div>
        <div class="md:col-span-2">
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-600">
                        <tr><th class="px-4 py-2 text-left">Nombre</th><th class="px-4 py-2">Herramientas</th><th></th></tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach($categories as $c)
                            <tr>
                                <td class="px-4 py-2">
                                    <div class="font-medium">{{ $c->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $c->description }}</div>
                                </td>
                                <td class="px-4 py-2 text-center">{{ $c->tools_count }}</td>
                                <td class="px-4 py-2 text-right">
                                    <form method="POST" action="{{ route('categories.destroy', $c) }}" onsubmit="return confirm('¿Eliminar?')" class="inline">
                                        @csrf @method('DELETE')
                                        <button class="text-red-600 text-xs hover:underline">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $categories->links() }}</div>
        </div>
    </div>
</x-app-layout>
