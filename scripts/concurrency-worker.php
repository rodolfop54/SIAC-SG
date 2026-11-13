<?php

use App\Models\Appointment;
use App\Models\AuditEvent;
use App\Models\Service;
use App\Models\Slot;
use App\Models\User;
use App\Services\AppointmentService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if ($argv[1] === 'prepare') {
    if (DB::getDatabaseName() !== 'siac_sg_test') {
        throw new RuntimeException('Concurrency fixtures require siac_sg_test.');
    }
    $service = Service::factory()->create();
    $users = User::factory(2)->create();
    $s = Slot::factory()->create(['service_id' => $service->id]);
    echo json_encode(['service' => $service->id, 'users' => $users->pluck('id')->all(), 'slot' => $s->id]);
} elseif ($argv[1] === 'book') {
    while (! file_exists($argv[4])) {
        usleep(1000);
    }
    try {
        $a = app(AppointmentService::class)->book(User::findOrFail($argv[2]), (int) $argv[3], (string) Str::uuid(), $argv[5]);
        echo json_encode(['result' => 'confirmed', 'code' => $a->code]);
    } catch (ValidationException $e) {
        echo json_encode(['result' => 'conflict']);
    }
} elseif ($argv[1] === 'verify') {
    $fixture = json_decode(file_get_contents($argv[2]), true);
    $count = Appointment::where('slot_id', $fixture['slot'])->where('status', 'CONFIRMADA')->count();
    $audit = AuditEvent::whereIn('actor_id', $fixture['users'])->count();
    echo json_encode(['active' => $count, 'audits' => $audit]);
    DB::transaction(function () use ($fixture) {
        AuditEvent::whereIn('actor_id', $fixture['users'])->delete();
        Appointment::where('slot_id', $fixture['slot'])->delete();
        Slot::whereKey($fixture['slot'])->delete();
        Service::whereKey($fixture['service'])->delete();
        User::whereIn('id', $fixture['users'])->delete();
    });
}
