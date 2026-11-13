<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Slot;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AppointmentService
{
    public function __construct(private AuditService $audit) {}

    private function conflict(string $message): never
    {
        throw ValidationException::withMessages(['conflict' => $message]);
    }

    private function assertFree(Slot $slot): void
    {
        if (! $slot->enabled || ! $slot->service->enabled || $slot->starts_at->isPast() || $slot->appointments()->where('status', 'CONFIRMADA')->exists()) {
            $this->conflict('Este horario ya no está disponible. Elige otro; tu reserva anterior se conserva.');
        }
    }

    public function book(User $user, int $slotId, string $key, string $channel = 'web'): Appointment
    {
        abort_unless($user->role === 'user', 403);

        return DB::transaction(function () use ($user, $slotId, $key, $channel) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $existing = Appointment::where('request_key', $key)->first();
            if ($existing) {
                abort_unless($existing->user_id === $user->id, 403);

                return $existing;
            }
            $slot = Slot::with('service')->lockForUpdate()->findOrFail($slotId);
            $this->assertFree($slot);
            $appointment = Appointment::create(['code' => (string) Str::uuid(), 'user_id' => $user->id, 'slot_id' => $slot->id, 'request_key' => $key, 'channel' => $channel, 'status' => 'CONFIRMADA']);
            $this->audit->record($user, $appointment, 'reserva', null, $appointment->only(['code', 'slot_id', 'status']), $channel);

            return $appointment;
        }, 3);
    }

    public function reschedule(User $user, Appointment $appointment, int $slotId, string $channel = 'web'): Appointment
    {
        Gate::forUser($user)->authorize('update', $appointment);

        return DB::transaction(function () use ($user, $appointment, $slotId, $channel) {
            $appointment = Appointment::lockForUpdate()->findOrFail($appointment->id);
            if ($appointment->status !== 'CONFIRMADA' || $appointment->slot->starts_at->isPast()) {
                $this->conflict('Solo puedes reprogramar citas confirmadas y futuras.');
            }
            if ($appointment->slot_id === $slotId) {
                return $appointment;
            }
            $slots = Slot::with('service')->whereIn('id', [$appointment->slot_id, $slotId])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $target = $slots->get($slotId);
            abort_unless($target, 404);
            if ($target->service_id !== $slots[$appointment->slot_id]->service_id) {
                $this->conflict('Selecciona un horario del mismo servicio.');
            }
            $this->assertFree($target);
            $before = $appointment->only(['slot_id', 'status']);
            $appointment->update(['slot_id' => $slotId]);
            $this->audit->record($user, $appointment, 'reprogramacion', $before, $appointment->only(['slot_id', 'status']), $channel);

            return $appointment;
        }, 3);
    }

    public function cancel(User $user, Appointment $appointment, string $channel = 'web'): Appointment
    {
        Gate::forUser($user)->authorize('update', $appointment);

        return DB::transaction(function () use ($user, $appointment, $channel) {
            $appointment = Appointment::lockForUpdate()->findOrFail($appointment->id);
            if ($appointment->status === 'CANCELADA') {
                return $appointment;
            }
            if ($appointment->slot->starts_at->isPast()) {
                $this->conflict('Esta cita ya pasó y solo puede consultarse en el historial.');
            }
            Slot::whereKey($appointment->slot_id)->lockForUpdate()->firstOrFail();
            $before = $appointment->only(['slot_id', 'status']);
            $appointment->update(['status' => 'CANCELADA', 'cancelled_at' => now()]);
            $this->audit->record($user, $appointment, 'cancelacion', $before, $appointment->only(['slot_id', 'status']), $channel);

            return $appointment;
        }, 3);
    }
}
