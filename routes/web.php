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
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ToolController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Herramientas
    Route::resource('tools', ToolController::class);
    Route::get('tools/{tool}/qr', [LabelController::class, 'qr'])->name('tools.qr');
    Route::get('tools/{tool}/label', [LabelController::class, 'show'])->name('tools.label');
    Route::get('labels/sheet', [LabelController::class, 'sheet'])->name('labels.sheet');

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
    });

    // Usuarios (solo super_admin)
    Route::middleware(['role:super_admin'])->group(function () {
        Route::resource('users', UserController::class);
    });
});

require __DIR__.'/auth.php';
