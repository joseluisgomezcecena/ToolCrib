<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="font-bold text-indigo-600">Nexus Tool Crib</a>
                </div>

                <div class="hidden space-x-6 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Dashboard</x-nav-link>

                    @can('tools.view')
                        <x-nav-link :href="route('tools.index')" :active="request()->routeIs('tools.*')">Herramientas</x-nav-link>
                    @endcan

                    @can('movements.view')
                        <x-nav-link :href="route('movements.index')" :active="request()->routeIs('movements.*')">Movimientos</x-nav-link>
                    @endcan

                    @hasrole('cliente')
                        <x-nav-link :href="route('mis.movimientos')" :active="request()->routeIs('mis.movimientos')">Mis herramientas</x-nav-link>
                    @endhasrole

                    @can('kiosk.operate')
                        <x-nav-link :href="route('kiosk.index')" :active="request()->routeIs('kiosk.*')">Kiosko</x-nav-link>
                    @endcan

                    @can('alerts.view')
                        <x-nav-link :href="route('alerts.index')" :active="request()->routeIs('alerts.*')">
                            Alertas
                            @php($unread = \App\Models\Alert::unresolved()->count())
                            @if($unread > 0)
                                <span id="nav-alert-badge" class="ml-1 inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-red-600 rounded-full">{{ $unread }}</span>
                            @else
                                <span id="nav-alert-badge" class="hidden ml-1 inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-red-600 rounded-full">0</span>
                            @endif
                        </x-nav-link>
                    @endcan

                    @can('reports.view')
                        <x-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')">Reportes</x-nav-link>
                    @endcan

                    @hasrole('super_admin')
                        <x-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')">Usuarios</x-nav-link>
                    @endhasrole
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}
                                <span class="text-xs text-gray-400">({{ Auth::user()->getRoleNames()->first() }})</span>
                            </div>
                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        @can('categories.manage')
                            <x-dropdown-link :href="route('categories.index')">Categorías</x-dropdown-link>
                            <x-dropdown-link :href="route('locations.index')">Ubicaciones</x-dropdown-link>
                            <x-dropdown-link :href="route('maintenances.index')">Mantenimientos</x-dropdown-link>
                        @endcan
                        <x-dropdown-link :href="route('profile.edit')">Perfil</x-dropdown-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">Cerrar sesión</x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Dashboard</x-responsive-nav-link>
            @can('tools.view')<x-responsive-nav-link :href="route('tools.index')">Herramientas</x-responsive-nav-link>@endcan
            @can('movements.view')<x-responsive-nav-link :href="route('movements.index')">Movimientos</x-responsive-nav-link>@endcan
            @can('alerts.view')<x-responsive-nav-link :href="route('alerts.index')">Alertas</x-responsive-nav-link>@endcan
            @can('reports.view')<x-responsive-nav-link :href="route('reports.index')">Reportes</x-responsive-nav-link>@endcan
            @hasrole('super_admin')<x-responsive-nav-link :href="route('users.index')">Usuarios</x-responsive-nav-link>@endhasrole
        </div>

        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>
            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">Perfil</x-responsive-nav-link>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">Cerrar sesión</x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
