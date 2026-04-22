<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Nuevo usuario</h2></x-slot>
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow p-6">
            <form method="POST" action="{{ route('users.store') }}">
                @include('users._form')
            </form>
        </div>
    </div>
</x-app-layout>
