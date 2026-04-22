<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Kiosko · {{ config('app.name') }}</title>
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
                   :placeholder="step === 'employee' ? 'Gafete del empleado' : (step === 'tool' ? 'Código de herramienta' : '')">
        </div>

        <div class="bg-slate-800 rounded-lg p-6 space-y-4">
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
                            <div class="text-sm text-slate-400" x-text="tool.code + ' · disp: ' + tool.qty_available"></div>
                            <template x-if="tool.blocked">
                                <div class="mt-2 text-red-400 text-sm" x-text="'BLOQUEADA: ' + tool.reason"></div>
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
                <div class="flex gap-2">
                    <button @click="qty = Math.max(1, qty - 1)" class="bg-slate-700 px-4 py-2 rounded-md text-xl">−</button>
                    <input x-model.number="qty" type="number" min="1" class="flex-1 bg-slate-900 border border-slate-600 rounded-md px-3 py-2 text-center text-2xl">
                    <button @click="qty = qty + 1" class="bg-slate-700 px-4 py-2 rounded-md text-xl">+</button>
                </div>
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
    </main>
</div>
</body>
</html>
