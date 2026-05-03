<?php

use App\Http\Controllers\AlertController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KioskController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\MovementController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ToolController;
use App\Http\Controllers\ToolItemController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::get('/manifest.webmanifest', function () {
    return response()->json([
        'name' => config('app.name', 'Nexus Tool Crib'),
        'short_name' => 'Tool Crib',
        'description' => 'Sistema de control de herramientas para maquiladora',
        'start_url' => url('/'),
        'scope' => url('/'),
        'display' => 'standalone',
        'orientation' => 'any',
        'background_color' => '#0f172a',
        'theme_color' => '#4f46e5',
        'lang' => 'es',
        'icons' => [
            [
                'src' => asset('icons/icon.svg'),
                'sizes' => 'any',
                'type' => 'image/svg+xml',
                'purpose' => 'any',
            ],
            [
                'src' => asset('icons/icon.svg'),
                'sizes' => '192x192 512x512',
                'type' => 'image/svg+xml',
                'purpose' => 'maskable',
            ],
        ],
        'shortcuts' => [
            ['name' => 'Kiosko', 'short_name' => 'Kiosko', 'url' => url('/kiosko')],
            ['name' => 'Movimientos', 'short_name' => 'Movs', 'url' => url('/movements')],
            ['name' => 'Alertas', 'short_name' => 'Alertas', 'url' => url('/alerts')],
        ],
    ], 200, [
        'Content-Type' => 'application/manifest+json',
        'Cache-Control' => 'public, max-age=3600',
    ]);
})->name('pwa.manifest');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Herramientas
    Route::resource('tools', ToolController::class);
    Route::get('tools/{tool}/qr', [LabelController::class, 'qr'])->name('tools.qr');
    Route::get('tools/{tool}/label', [LabelController::class, 'show'])->name('tools.label');
    Route::get('tools/{tool}/items/{item}/qr', [LabelController::class, 'itemQr'])->name('items.qr');
    Route::get('tools/{tool}/items/{item}/label', [LabelController::class, 'itemShow'])->name('items.label');
    Route::get('labels/sheet', [LabelController::class, 'sheet'])->name('labels.sheet');
    Route::get('tools/{tool}/items/sheet', [LabelController::class, 'itemsSheet'])->name('items.sheet');

    Route::post('tools/{tool}/items', [ToolItemController::class, 'store'])->name('items.store');
    Route::put('tools/{tool}/items/{item}', [ToolItemController::class, 'update'])->name('items.update');
    Route::delete('tools/{tool}/items/{item}', [ToolItemController::class, 'destroy'])->name('items.destroy');

    // Movimientos
    Route::get('movements', [MovementController::class, 'index'])->name('movements.index');
    Route::get('movements/checkout', [MovementController::class, 'createCheckout'])->name('movements.checkout');
    Route::post('movements/checkout', [MovementController::class, 'storeCheckout'])->name('movements.checkout.store');
    Route::post('movements/{movement}/checkin', [MovementController::class, 'checkin'])->name('movements.checkin');
    Route::post('tools/{tool}/scrap', [MovementController::class, 'storeScrap'])->name('tools.scrap');
    Route::post('tools/{tool}/transfer', [MovementController::class, 'storeTransfer'])->name('tools.transfer');
    Route::post('tools/{tool}/receipt', [MovementController::class, 'storeReceipt'])->name('tools.receipt');

    Route::get('mis-herramientas', [MovementController::class, 'myMovements'])->name('mis.movimientos');

    // Alertas
    Route::get('alerts', [AlertController::class, 'index'])->name('alerts.index');
    Route::post('alerts/{alert}/read', [AlertController::class, 'markRead'])->name('alerts.read');
    Route::post('alerts/{alert}/resolve', [AlertController::class, 'resolve'])->name('alerts.resolve');

    // Catálogos (solo toolcrib/super_admin)
    Route::middleware(['role:super_admin|toolcrib'])->group(function () {
        Route::get('compras', [PurchaseController::class, 'index'])->name('purchases.index');
        Route::get('compras/create', [PurchaseController::class, 'create'])->name('purchases.create');
        Route::post('compras', [PurchaseController::class, 'store'])->name('purchases.store');

        Route::resource('categories', CategoryController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('locations', LocationController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::get('maintenances', [MaintenanceController::class, 'index'])->name('maintenances.index');
        Route::get('maintenances/create', [MaintenanceController::class, 'create'])->name('maintenances.create');
        Route::post('maintenances', [MaintenanceController::class, 'store'])->name('maintenances.store');
        Route::post('maintenances/{maintenance}/complete', [MaintenanceController::class, 'complete'])->name('maintenances.complete');

        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/consumption', [ReportController::class, 'consumption'])->name('reports.consumption');
        Route::get('reports/usage', [ReportController::class, 'usage'])->name('reports.usage');

        // Kiosko
        Route::get('kiosko', [KioskController::class, 'index'])->name('kiosk.index');
        Route::get('kiosko/tool', [KioskController::class, 'lookupTool'])->name('kiosk.tool');
        Route::get('kiosko/employee', [KioskController::class, 'lookupEmployee'])->name('kiosk.employee');
        Route::post('kiosko/commit', [KioskController::class, 'commit'])->name('kiosk.commit');
        Route::get('kiosko/checkin/lookup', [KioskController::class, 'lookupCheckin'])->name('kiosk.checkin.lookup');
        Route::post('kiosko/checkin', [KioskController::class, 'commitCheckin'])->name('kiosk.checkin');
    });

    // Usuarios (solo super_admin)
    Route::middleware(['role:super_admin'])->group(function () {
        Route::resource('users', UserController::class);
    });
});

require __DIR__.'/auth.php';
