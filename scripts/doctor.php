<?php

use App\Models\Appointment;
use App\Models\AuditEvent;
use App\Models\Service;
use App\Models\Slot;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$db = DB::selectOne("select version() as version, current_setting('TimeZone') as timezone");
echo json_encode(['php' => PHP_VERSION, 'laravel' => $app->version(), 'postgres' => $db->version, 'database_timezone' => $db->timezone, 'app_timezone' => config('app.timezone'), 'session_minutes' => config('session.lifetime'), 'counts' => ['users' => User::count(), 'services' => Service::count(), 'slots' => Slot::count(), 'appointments' => Appointment::count(), 'audits' => AuditEvent::count()]], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
