<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\Slot;
use App\Models\User;
use App\Services\AppointmentService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $password = env('DEMO_PASSWORD');
        if (! $password) {
            throw new \RuntimeException('Define DEMO_PASSWORD antes de ejecutar la semilla sintética.');
        }
        DB::transaction(function () use ($password) {
            $users = collect();
            foreach ([['DEMO-ALBA', 'Alba · cuenta ficticia', 'user'], ['DEMO-LEO', 'Leo · cuenta ficticia', 'user'], ['DEMO-SOL', 'Sol · cuenta ficticia', 'user'], ['DEMO-ADMIN', 'Administración de prueba', 'admin']] as [$alias,$name,$role]) {
                $users->push(User::firstOrCreate(['alias' => $alias], ['name' => $name, 'role' => $role, 'password' => $password]));
            }
            $services = collect();
            foreach ([['Consulta general', 'Orientación general de prueba. Sin atención médica real.', 30], ['Odontología', 'Agenda sintética de salud oral.', 30], ['Enfermería', 'Consulta ficticia de primer nivel.', 20], ['Nutrición', 'Agenda académica de orientación nutricional.', 40]] as [$name,$description,$minutes]) {
                $services->push(Service::firstOrCreate(['name' => $name], ['description' => $description, 'duration_minutes' => $minutes]));
            }
            foreach ($services as $service) {
                for ($day = -3; $day <= 21; $day++) {
                    $date = now()->startOfDay()->addDays($day);
                    if ($date->isSunday()) {
                        continue;
                    }
                    foreach ([8, 9, 10, 11, 14, 15] as $hour) {
                        $start = $date->copy()->setHour($hour);
                        Slot::firstOrCreate(['service_id' => $service->id, 'starts_at' => $start], ['ends_at' => $start->copy()->addMinutes($service->duration_minutes), 'enabled' => ! ($day === 3 && $hour === 14)]);
                    }
                }
            }
            if (Appointment::exists()) {
                return;
            }
            $booking = app(AppointmentService::class);
            foreach ([1, 2, 4, 6, 8, 10] as $i => $day) {
                $slot = Slot::available()->where('service_id', $services[$i % 4]->id)->whereDate('starts_at', now()->addDays($day)->toDateString())->orderBy('starts_at')->first();
                if (! $slot) {
                    continue;
                }
                $u = $users[$i % 3];
                $a = $booking->book($u, $slot->id, (string) Str::uuid(), $i % 2 ? 'bot' : 'web');
                if ($i === 4) {
                    $booking->cancel($u, $a);
                }
            }
        });
    }
}
