<?php

namespace App\Services;

use App\Models\Service;
use App\Models\Slot;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AgendaService
{
    public function __construct(private AuditService $audit) {}

    public function create(User $actor, int $serviceId, string $start): Slot
    {
        abort_unless($actor->role === 'admin', 403);

        return DB::transaction(function () use ($actor, $serviceId, $start) {
            $service = Service::lockForUpdate()->findOrFail($serviceId);
            $starts = CarbonImmutable::parse($start, 'America/Bogota');
            $ends = $starts->addMinutes($service->duration_minutes);
            if (! $service->enabled || $starts->isPast()) {
                throw ValidationException::withMessages(['starts_at' => 'Elige una fecha futura y un servicio habilitado.']);
            }
            if (Slot::where('service_id', $serviceId)->where('starts_at', '<', $ends)->where('ends_at', '>', $starts)->exists()) {
                throw ValidationException::withMessages(['starts_at' => 'La franja se cruza con otro horario de este servicio.']);
            }
            $slot = Slot::create(['service_id' => $serviceId, 'starts_at' => $starts, 'ends_at' => $ends, 'enabled' => true]);
            $this->audit->record($actor, $slot, 'crear_franja', null, $slot->only(['service_id', 'starts_at', 'ends_at', 'enabled']));

            return $slot;
        }, 3);
    }

    public function toggle(User $actor, Slot $slot, bool $enabled): void
    {
        abort_unless($actor->role === 'admin', 403);
        DB::transaction(function () use ($actor, $slot, $enabled) {
            $slot = Slot::lockForUpdate()->findOrFail($slot->id);
            if (! $enabled && $slot->appointments()->where('status', 'CONFIRMADA')->exists()) {
                throw ValidationException::withMessages(['conflict' => 'No puedes bloquear una franja ocupada. La cita existente se conserva.']);
            }
            if ($slot->enabled === $enabled) {
                return;
            }
            $before = $slot->only('enabled');
            $slot->update(['enabled' => $enabled]);
            $this->audit->record($actor, $slot, $enabled ? 'habilitar_franja' : 'bloquear_franja', $before, $slot->only('enabled'));
        }, 3);
    }
}
