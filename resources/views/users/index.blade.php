<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl">Usuarios</h2>
            <a href="{{ route('users.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm">+ Nuevo</a>
        </div>
    </x-slot>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-2 text-left">Nombre</th>
                        <th class="px-4 py-2 text-left">Email</th>
                        <th class="px-4 py-2">Gafete</th>
                        <th class="px-4 py-2">Depto</th>
                        <th class="px-4 py-2">Rol</th>
                        <th class="px-4 py-2">Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($users as $u)
                        <tr>
                            <td class="px-4 py-2 font-medium">{{ $u->name }}</td>
                            <td class="px-4 py-2 text-gray-600">{{ $u->email }}</td>
                            <td class="px-4 py-2 text-center font-mono text-xs">{{ $u->employee_code }}</td>
                            <td class="px-4 py-2 text-center text-gray-600">{{ $u->department }}</td>
                            <td class="px-4 py-2 text-center">
                                @foreach($u->roles as $r)
                                    <span class="text-xs bg-indigo-100 text-indigo-800 px-2 py-0.5 rounded">{{ $r->name }}</span>
                                @endforeach
                            </td>
                            <td class="px-4 py-2 text-center">
                                @if($u->is_active)
                                    <span class="text-xs bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded">activo</span>
                                @else
                                    <span class="text-xs bg-gray-100 text-gray-800 px-2 py-0.5 rounded">inactivo</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-right">
                                <a href="{{ route('users.edit', $u) }}" class="text-gray-600 text-xs hover:underline">Editar</a>
                                @if($u->id !== auth()->id())
                                    <form method="POST" action="{{ route('users.destroy', $u) }}" onsubmit="return confirm('¿Eliminar?')" class="inline ml-2">
                                        @csrf @method('DELETE')
                                        <button class="text-red-600 text-xs hover:underline">Eliminar</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $users->links() }}</div>
    </div>
</x-app-layout>
