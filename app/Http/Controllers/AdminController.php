<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AuditEvent;
use App\Models\Service;
use App\Models\Slot;
use App\Services\AgendaService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function agenda(Request $r)
    {
        $r->validate(['date' => 'nullable|date_format:Y-m-d', 'service_id' => 'nullable|integer|exists:services,id']);
        $date = $r->date ?? now()->addDay()->toDateString();

        return Inertia::render('Agenda', ['services' => Service::orderBy('id')->get(), 'filters' => ['date' => $date, 'service_id' => $r->service_id ?? ''],
            'slots' => Slot::with('service')->withCount(['appointments as active_count' => fn ($q) => $q->where('status', 'CONFIRMADA')])->whereDate('starts_at', $date)->when($r->service_id, fn ($q) => $q->where('service_id', $r->service_id))->orderBy('starts_at')->paginate(20)->withQueryString()]);
    }

    public function store(Request $r, AgendaService $service)
    {
        $d = $r->validate(['service_id' => 'required|integer|exists:services,id', 'starts_at' => 'required|date_format:Y-m-d\TH:i|after:now']);
        $service->create($r->user(), $d['service_id'], $d['starts_at']);

        return back()->with('success', 'Franja creada y disponible para reservas.');
    }

    public function toggle(Request $r, Slot $slot, AgendaService $service)
    {
        $d = $r->validate(['enabled' => 'required|boolean']);
        $service->toggle($r->user(), $slot, $d['enabled']);

        return back()->with('success', $d['enabled'] ? 'Franja habilitada.' : 'Franja bloqueada.');
    }

    public function audit(Request $r)
    {
        $r->validate(['operation' => 'nullable|string|max:40']);

        return Inertia::render('Audit', ['events' => AuditEvent::with('actor:id,alias')->when($r->operation, fn ($q) => $q->where('operation', $r->operation))->latest('id')->paginate(20)->withQueryString(), 'operation' => $r->operation ?? '']);
    }

    public function reports(Request $r)
    {
        $d = $r->validate(['from' => 'nullable|date_format:Y-m-d', 'to' => 'nullable|date_format:Y-m-d|after_or_equal:from', 'service_id' => 'nullable|integer|exists:services,id']);
        $from = $d['from'] ?? now()->startOfMonth()->toDateString();
        $to = $d['to'] ?? now()->addMonth()->endOfMonth()->toDateString();
        $base = Appointment::join('slots', 'appointments.slot_id', '=', 'slots.id')->join('services', 'slots.service_id', '=', 'services.id')->whereDate('slots.starts_at', '>=', $from)->whereDate('slots.starts_at', '<=', $to)->when($r->service_id, fn ($q) => $q->where('service_id', $r->service_id));

        return Inertia::render('Reports', ['filters' => ['from' => $from, 'to' => $to, 'service_id' => $r->service_id ?? ''], 'services' => Service::all(),
            'byStatus' => (clone $base)->selectRaw('appointments.status, count(*)::int as total')->groupBy('appointments.status')->get(),
            'byService' => (clone $base)->selectRaw("services.name, count(*)::int as total, count(*) filter (where appointments.status = 'CONFIRMADA')::int as confirmed, count(*) filter (where appointments.status = 'CANCELADA')::int as cancelled")->groupBy('services.name')->orderBy('services.name')->get(),
            'byDay' => (clone $base)->selectRaw("to_char(slots.starts_at AT TIME ZONE 'America/Bogota','YYYY-MM-DD') as date, count(*)::int as total")->groupByRaw("to_char(slots.starts_at AT TIME ZONE 'America/Bogota','YYYY-MM-DD')")->orderBy('date')->get()]);
    }
}
