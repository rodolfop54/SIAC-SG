<?php

use App\Models\User;
use App\Services\ReminderService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Artisan;

Artisan::command('siac:recordatorios {--at= : Reloj ISO de prueba, por defecto ahora}', function () {
    try {
        $clock = CarbonImmutable::parse($this->option('at') ?? 'now', 'America/Bogota');
    } catch (Throwable $e) {
        $this->error('Indica un reloj ISO válido.');

        return 1;
    }
    $total = 0;
    foreach (User::where('role', 'user')->cursor() as $user) {
        $total += app(ReminderService::class)->emit($user, $clock);
    }
    $this->info($total.' recordatorios internos creados. Ningún mensaje externo enviado.');
})->purpose('Emitir recordatorios ficticios únicos dentro del simulador');
