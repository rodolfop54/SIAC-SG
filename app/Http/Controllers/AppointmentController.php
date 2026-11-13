<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Service;
use App\Services\AppointmentService;
use App\Services\AvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AppointmentController extends Controller
{
    public function availability(Request $r, AvailabilityService $availability)
    {
        $data = $r->validate(['service_id' => 'nullable|integer|exists:services,id', 'date' => 'nullable|date_format:Y-m-d', 'month' => 'nullable|date_format:Y-m', 'appointment' => 'nullable|integer']);
        $services = Service::where('enabled', true)->orderBy('id')->get();
        $appointment = isset($data['appointment']) ? Appointment::with('slot.service')->findOrFail($data['appointment']) : null;
        if ($appointment) {
            Gate::authorize('update', $appointment);
            abort_if($appointment->status !== 'CONFIRMADA' || $appointment->slot->starts_at->isPast(), 422, 'Esta cita no admite cambios.');
        }
        $serviceId = $appointment?->slot->service_id ?? ($data['service_id'] ?? $services->first()?->id);
        $date = $data['date'] ?? now()->addDay()->toDateString();
        $month = $data['month'] ?? substr($date, 0, 7);

        return Inertia::render('Availability', [
            'services' => $services, 'filters' => ['service_id' => $serviceId, 'date' => $date, 'month' => $month],
            'slots' => $availability->query($serviceId)->whereDate('starts_at', $date)->get(),
            'availableDates' => $availability->query($serviceId)->reorder()->where('starts_at', '>=', $month.'-01')->where('starts_at', '<', CarbonImmutable::parse($month.'-01')->addMonth())->selectRaw("distinct to_char(starts_at AT TIME ZONE 'America/Bogota', 'YYYY-MM-DD') as date")->pluck('date'),
            'appointment' => $appointment, 'requestKey' => (string) Str::uuid(),
        ]);
    }

    public function index(Request $r)
    {
        $r->validate(['status' => 'nullable|in:CONFIRMADA,CANCELADA']);

        return Inertia::render('Appointments', ['appointments' => Appointment::with('slot.service')->where('user_id', $r->user()->id)->when($r->status, fn ($q) => $q->where('status', $r->status))->orderByDesc('created_at')->paginate(12)->withQueryString(), 'status' => $r->status ?? '']);
    }

    public function show(Appointment $appointment)
    {
        Gate::authorize('view', $appointment);

        return Inertia::render('Confirmation', ['appointment' => $appointment->load('slot.service')]);
    }

    public function store(Request $r, AppointmentService $service)
    {
        $data = $r->validate(['slot_id' => 'required|integer|exists:slots,id', 'request_key' => 'required|uuid', 'confirmed' => 'accepted']);
        $a = $service->book($r->user(), $data['slot_id'], $data['request_key']);

        return redirect('/citas/'.$a->id)->with('success', 'Tu cita de prueba quedó confirmada.');
    }

    public function update(Request $r, Appointment $appointment, AppointmentService $service)
    {
        $data = $r->validate(['slot_id' => 'required|integer|exists:slots,id', 'confirmed' => 'accepted']);
        $a = $service->reschedule($r->user(), $appointment, $data['slot_id']);

        return redirect('/citas/'.$a->id)->with('success', 'El horario se actualizó. Tu código de reserva se conserva.');
    }

    public function cancel(Request $r, Appointment $appointment, AppointmentService $service)
    {
        $r->validate(['confirmed' => 'accepted']);
        $service->cancel($r->user(), $appointment);

        return redirect('/mis-citas')->with('success', 'Cita cancelada. El horario vuelve a estar disponible.');
    }
}
