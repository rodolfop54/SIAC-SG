<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\SimulatedMessage;
use App\Models\User;
use Carbon\CarbonInterface;

class ReminderService
{
    public function emit(User $user, CarbonInterface $clock): int
    {
        $count = 0;
        foreach (Appointment::with('slot.service')->where('user_id', $user->id)->where('status', 'CONFIRMADA')->whereHas('slot', fn ($q) => $q->whereBetween('starts_at', [$clock, $clock->copy()->addHours(24)]))->get() as $a) {
            $message = SimulatedMessage::firstOrCreate(['event_key' => 'reminder:'.$a->id.':'.$a->slot_id.':'.$a->slot->starts_at->timestamp], ['user_id' => $user->id, 'appointment_id' => $a->id, 'sender' => 'bot', 'type' => 'reminder', 'content' => 'Recordatorio interno: '.$a->slot->service->name.' · '.$a->slot->starts_at->format('d/m/Y H:i').' · código '.$a->code.'. Es una cita ficticia.']);
            $count += (int) $message->wasRecentlyCreated;
        }

        return $count;
    }
}
