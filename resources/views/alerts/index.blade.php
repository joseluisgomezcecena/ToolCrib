<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Alertas</h2></x-slot>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        <form method="GET" class="bg-white p-3 rounded-md shadow-sm flex flex-wrap gap-2 items-end">
            <label class="inline-flex items-center text-sm gap-1">
                <input type="checkbox" name="unresolved" value="1" @checked(request('unresolved', true))> Solo sin resolver
            </label>
            <select name="severity" class="border rounded-md px-3 py-1.5 text-sm">
                <option value="">Cualquier severidad</option>
                <option value="critical" @selected(request('severity')==='critical')>Crítica</option>
                <option value="warning" @selected(request('severity')==='warning')>Advertencia</option>
                <option value="info" @selected(request('severity')==='info')>Info</option>
            </select>
            <button class="bg-gray-800 text-white px-3 py-1.5 rounded-md text-sm">Filtrar</button>
        </form>

        <div class="bg-white rounded-lg shadow">
            <ul class="divide-y">
                @forelse($alerts as $a)
                    <li class="p-4 flex items-start gap-3 {{ $a->read_at ? '' : 'bg-amber-50/40' }}">
                        <span @class([
                            'inline-block w-3 h-3 rounded-full mt-1.5 shrink-0',
                            'bg-red-500' => $a->severity === 'critical',
                            'bg-amber-500' => $a->severity === 'warning',
                            'bg-blue-500' => $a->severity === 'info',
                        ])></span>
                        <div class="flex-1">
                            <div class="flex justify-between items-start">
                                <div>
                                    <div class="font-semibold">{{ $a->title }}</div>
                                    <div class="text-sm text-gray-700">{{ $a->message }}</div>
                                    <div class="text-xs text-gray-400 mt-1">
                                        {{ $a->created_at->diffForHumans() }}
                                        @if($a->tool) · <a href="{{ route('tools.show', $a->tool) }}" class="text-indigo-600">{{ $a->tool->name }}</a> @endif
                                        @if($a->user) · {{ $a->user->name }} @endif
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    @unless($a->read_at)
                                        <form method="POST" action="{{ route('alerts.read', $a) }}">@csrf
                                            <button class="text-xs px-2 py-1 border rounded-md">Marcar leída</button>
                                        </form>
                                    @endunless
                                    @can('alerts.resolve')
                                        @unless($a->resolved_at)
                                            <form method="POST" action="{{ route('alerts.resolve', $a) }}">@csrf
                                                <button class="text-xs px-2 py-1 bg-emerald-600 text-white rounded-md">Resolver</button>
                                            </form>
                                        @endunless
                                    @endcan
                                </div>
                            </div>
                        </div>
                    </li>
                @empty
                    <li class="p-8 text-center text-gray-500">Sin alertas.</li>
                @endforelse
            </ul>
        </div>

        @include('partials.pagination', ['paginator' => $alerts])
    </div>
</x-app-layout>
