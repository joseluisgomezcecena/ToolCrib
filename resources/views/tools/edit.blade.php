<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Editar: {{ $tool->name }}</h2></x-slot>
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow p-6">
            <form method="POST" action="{{ route('tools.update', $tool) }}">
                @method('PUT')
                @include('tools._form')
            </form>
        </div>

        @can('tools.delete')
            <form method="POST" action="{{ route('tools.destroy', $tool) }}" class="mt-4"
                  onsubmit="return confirm('¿Eliminar definitivamente?')">
                @csrf @method('DELETE')
                <button class="text-sm text-red-600 hover:underline">Eliminar herramienta</button>
            </form>
        @endcan
    </div>
</x-app-layout>
