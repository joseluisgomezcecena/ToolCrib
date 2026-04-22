<?php

use App\Services\MovementService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('alerts:scan-overdue', function (MovementService $svc) {
    $n = $svc->scanOverdueReturns();
    $this->info("Alertas creadas: {$n}");
})->purpose('Escanea devoluciones vencidas y genera alertas');

Schedule::command('alerts:scan-overdue')->everyFifteenMinutes();
