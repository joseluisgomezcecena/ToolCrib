<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Kiosko · {{ config('app.name') }}</title>
    @include('partials.pwa-meta')
    @vite(['resources/css/app.css', 'resources/js/kiosk.js'])
    <style>
        body { background: #0f172a; color: #fff; }
        .stage { min-height: 100vh; }
        #qr-reader { border: 4px dashed #38bdf8; border-radius: 14px; overflow: hidden; background: #0f172a; }
        #qr-reader video { width: 100% !important; height: auto !important; }
    </style>
    <script>
        window.KIOSK_ROUTES = {
            employee: @json(route('kiosk.employee')),
            tool: @json(route('kiosk.tool')),
            commit: @json(route('kiosk.commit')),
            checkinLookup: @json(route('kiosk.checkin.lookup')),
            checkin: @json(route('kiosk.checkin')),
        };
        window.KIOSK_LOCATIONS = @json($locations);
    </script>
</head>
<body>
<div class="stage flex flex-col" x-data="kioskApp()" x-init="init()">
    <header class="flex justify-between items-center px-6 py-4 bg-slate-800">
        <div>
            <h1 class="text-2xl font-bold">Nexus Tool Crib · Kiosko</h1>
            <p class="text-xs text-slate-400">Operador: {{ auth()->user()->name }}</p>
        </div>
        <div class="flex gap-3 items-center">
            <a href="{{ route('dashboard') }}" class="text-slate-300 hover:text-white text-sm">← Dashboard</a>
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button class="text-slate-300 hover:text-white text-sm">Cerrar sesión</button>
            </form>
        </div>
    </header>

    <div class="flex justify-center bg-slate-800 border-b border-slate-700">
        <button @click="switchMode('checkout')"
                :class="mode === 'checkout' ? 'bg-emerald-600 text-white' : 'text-slate-400 hover:text-white'"
                class="px-8 py-3 font-bold text-lg flex-1 max-w-xs">
            ⬆️ SACAR
        </button>
        <button @click="switchMode('checkin')"
                :class="mode === 'checkin' ? 'bg-blue-600 text-white' : 'text-slate-400 hover:text-white'"
                class="px-8 py-3 font-bold text-lg flex-1 max-w-xs">
            ⬇️ DEVOLVER
        </button>
    </div>

    <main class="flex-1 p-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="space-y-4">
            <h2 class="text-xl font-semibold" x-text="stepTitle"></h2>

            <div class="flex flex-wrap gap-2 items-center" x-show="cameras.length > 0">
                <label class="text-sm text-slate-400">Cámara:</label>
                <select @change="switchCamera($event)" class="bg-slate-800 border border-slate-600 rounded px-3 py-1.5 text-sm">
                    <template x-for="cam in cameras" :key="cam.id">
                        <option :value="cam.id" x-text="cam.label || cam.id"></option>
                    </template>
                </select>
                <button @click="startCamera()" class="bg-sky-600 hover:bg-sky-700 px-3 py-1.5 rounded text-sm">Reiniciar cámara</button>
            </div>

            <div id="qr-reader" class="w-full aspect-video"></div>

            <div x-show="scannerError" class="bg-red-900/60 border border-red-500 rounded p-3 text-sm" x-text="scannerError"></div>

            <div class="text-sm text-slate-400">O usa lector USB / escribe manual:</div>
            <input type="text" x-model="manualCode" @keydown.enter.prevent="handleScan(manualCode)"
                   class="w-full bg-slate-800 border border-slate-600 rounded-md px-4 py-3 text-lg font-mono"
                   :placeholder="mode === 'checkin' ? 'Tag o código de la herramienta a devolver' : (step === 'employee' ? 'Gafete del empleado' : (step === 'tool' ? 'Código de herramienta' : ''))">
        </div>

        <div x-show="mode === 'checkout'" class="bg-slate-800 rounded-lg p-6 space-y-4">
            <div class="space-y-2">
                <div class="text-xs uppercase text-slate-400">1. Empleado</div>
                <div class="bg-slate-900 rounded-md p-3 min-h-[70px]">
                    <template x-if="employee">
                        <div>
                            <div class="font-bold text-lg" x-text="employee.name"></div>
                            <div class="text-sm text-slate-400" x-text="(employee.department || '—') + ' · ' + employee.employee_code"></div>
                        </div>
                    </template>
                    <template x-if="!employee">
                        <div class="text-slate-500">Escanee gafete o capture el código</div>
                    </template>
                </div>
            </div>

            <div class="space-y-2">
                <div class="text-xs uppercase text-slate-400">2. Herramienta</div>
                <div class="bg-slate-900 rounded-md p-3 min-h-[70px]">
                    <template x-if="tool">
                        <div>
                            <div class="font-bold text-lg" x-text="tool.name"></div>
                            <div class="text-sm text-slate-400">
                                <span x-text="tool.code"></span>
                                <template x-if="tool.tracking_mode === 'serialized'">
                                    <span class="ml-2 px-1.5 py-0.5 bg-sky-700 text-sky-100 rounded text-xs">INSTANCIA</span>
                                </template>
                                <template x-if="tool.tracking_mode === 'bulk'">
                                    <span class="ml-2 text-xs">disp: <span x-text="tool.qty_available"></span></span>
                                </template>
                            </div>
                            <template x-if="tool.blocked">
                                <div class="mt-2 text-red-400 text-sm" x-text="'BLOQUEADA: ' + (tool.reason || 'no disponible')"></div>
                            </template>
                        </div>
                    </template>
                    <template x-if="!tool">
                        <div class="text-slate-500">Escanee código de herramienta</div>
                    </template>
                </div>
            </div>

            <div class="space-y-2" x-show="employee && tool && !tool.blocked">
                <div class="text-xs uppercase text-slate-400">3. Cantidad</div>
                <template x-if="tool && tool.tracking_mode === 'serialized'">
                    <div class="bg-slate-900 rounded-md p-3 text-center text-2xl font-bold">1 pieza (instancia)</div>
                </template>
                <template x-if="!tool || tool.tracking_mode !== 'serialized'">
                    <div class="flex gap-2">
                        <button @click="qty = Math.max(1, qty - 1)" class="bg-slate-700 px-4 py-2 rounded-md text-xl">−</button>
                        <input x-model.number="qty" type="number" min="1" class="flex-1 bg-slate-900 border border-slate-600 rounded-md px-3 py-2 text-center text-2xl">
                        <button @click="qty = qty + 1" class="bg-slate-700 px-4 py-2 rounded-md text-xl">+</button>
                    </div>
                </template>
                <input x-model="workOrder" placeholder="Orden de producción" class="w-full bg-slate-900 border border-slate-600 rounded-md px-3 py-2 text-sm">

                <div class="space-y-1">
                    <label class="text-xs uppercase text-slate-400">Ubicación destino *</label>
                    <select x-model.number="toLocationId" class="w-full bg-slate-900 border border-slate-600 rounded-md px-3 py-2 text-sm">
                        <option value="">— elige —</option>
                        <template x-for="loc in locations" :key="loc.id">
                            <option :value="loc.id" x-text="loc.name + ' (' + loc.type + ')'"></option>
                        </template>
                    </select>
                </div>

                <div x-show="tool && tool.type === 'durable'" class="space-y-1">
                    <label class="text-xs uppercase text-slate-400">Regresar antes de</label>
                    <input type="datetime-local" x-model="returnDueAt"
                           class="w-full bg-slate-900 border border-slate-600 rounded-md px-3 py-2 text-sm">
                    <div class="text-xs text-slate-500">Solo aplica a herramientas que sí regresan (durables).</div>
                </div>
                <div x-show="tool && tool.type === 'consumible'" class="text-xs text-orange-300 bg-orange-900/20 border border-orange-700/40 rounded-md p-2">
                    Consumible: no regresa, se baja del stock definitivamente.
                </div>

                <button @click="commit()" :disabled="sending"
                        class="w-full bg-emerald-500 hover:bg-emerald-600 text-white py-4 rounded-md text-xl font-bold disabled:opacity-50">
                    Registrar salida
                </button>
            </div>

            <div x-show="message" class="mt-4 p-3 rounded-md"
                 :class="messageType === 'error' ? 'bg-red-900 text-red-200' : 'bg-emerald-900 text-emerald-200'"
                 x-text="message"></div>

            <button @click="reset()" class="w-full mt-2 text-slate-400 hover:text-white text-sm">Reiniciar transacción</button>
        </div>

        <div x-show="mode === 'checkin'" class="bg-slate-800 rounded-lg p-6 space-y-4">
            <div x-show="pendingMovements.length === 0" class="text-slate-400 text-center py-12">
                <div class="text-5xl mb-2">📥</div>
                <div>Escanea el tag o código de la herramienta a devolver.</div>
                <div class="text-xs text-slate-500 mt-2">El sistema buscará automáticamente la salida abierta.</div>
            </div>

            <template x-if="pendingMovements.length > 1 && !selectedMovement">
                <div class="space-y-2">
                    <div class="text-xs uppercase text-slate-400">Hay <span x-text="pendingMovements.length"></span> salidas abiertas. Selecciona cuál:</div>
                    <template x-for="m in pendingMovements" :key="m.id">
                        <button @click="pickCheckin(m)"
                                class="w-full text-left bg-slate-900 hover:bg-slate-700 rounded-md p-3 border border-slate-700">
                            <div class="font-bold text-lg" x-text="m.tool_name"></div>
                            <div class="text-sm text-slate-400">
                                Cliente: <span x-text="m.customer"></span> ·
                                Salida: <span x-text="m.occurred_at"></span> ·
                                qty: <span x-text="m.qty"></span>
                            </div>
                            <div class="text-xs" :class="m.overdue ? 'text-red-400 font-bold' : 'text-slate-500'">
                                Vence: <span x-text="m.return_due_at || '—'"></span>
                                <span x-show="m.overdue">· VENCIDA</span>
                            </div>
                        </button>
                    </template>
                </div>
            </template>

            <template x-if="selectedMovement">
                <div class="space-y-3">
                    <div class="text-xs uppercase text-slate-400">Confirmar devolución</div>
                    <div class="bg-slate-900 rounded-md p-4 space-y-1 border" :class="selectedMovement.overdue ? 'border-red-500' : 'border-slate-700'">
                        <div class="font-bold text-2xl" x-text="selectedMovement.tool_name"></div>
                        <div class="font-mono text-blue-300" x-text="selectedMovement.tag"></div>
                        <div class="text-sm text-slate-400 mt-2">
                            Cliente: <span class="text-white" x-text="selectedMovement.customer"></span>
                        </div>
                        <div class="text-sm text-slate-400">
                            Salida: <span x-text="selectedMovement.occurred_at"></span> ·
                            qty <span x-text="selectedMovement.qty"></span>
                        </div>
                        <div class="text-sm" :class="selectedMovement.overdue ? 'text-red-400 font-bold' : 'text-slate-400'">
                            Vence: <span x-text="selectedMovement.return_due_at || '—'"></span>
                            <span x-show="selectedMovement.overdue">· VENCIDA</span>
                        </div>
                        <div x-show="selectedMovement.work_order" class="text-sm text-slate-400">
                            OP: <span class="text-white" x-text="selectedMovement.work_order"></span>
                        </div>
                        <div x-show="selectedMovement.destination" class="text-sm text-slate-400">
                            Destino: <span class="text-white" x-text="selectedMovement.destination"></span>
                        </div>
                    </div>
                    <button @click="commitCheckin()" :disabled="sending"
                            class="w-full bg-blue-500 hover:bg-blue-600 text-white py-4 rounded-md text-xl font-bold disabled:opacity-50">
                        ✓ Confirmar devolución
                    </button>
                    <button @click="reset()" class="w-full text-slate-400 hover:text-white text-sm">Cancelar y escanear otra</button>
                </div>
            </template>

            <div x-show="message" class="mt-4 p-3 rounded-md"
                 :class="messageType === 'error' ? 'bg-red-900 text-red-200' : 'bg-emerald-900 text-emerald-200'"
                 x-text="message"></div>
        </div>
    </main>
</div>
</body>
</html>
