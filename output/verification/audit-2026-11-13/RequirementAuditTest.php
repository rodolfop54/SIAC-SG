<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AuditEvent;
use App\Models\Service;
use App\Models\SimulatedMessage;
use App\Models\Slot;
use App\Models\User;
use App\Services\AgendaService;
use App\Services\AppointmentService;
use App\Services\BotService;
use App\Services\PatientIdentityService;
use App\Services\ReminderService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/** Exploratory audit: intentionally preserves failing acceptance checks as evidence. */
class RequirementAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->assertSame('siac_sg_test', \Illuminate\Support\Facades\DB::getDatabaseName());
    }

    public function test_rf02_empty_availability_is_successful_and_has_no_slots(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create();
        $date = now()->addDays(7)->toDateString();
        $this->actingAs($user)->get('/disponibilidad?service_id='.$service->id.'&date='.$date)
            ->assertOk()->assertInertia(fn (Assert $p) => $p->has('slots', 0)->has('availableDates', 0));
    }

    public function test_rf08_all_six_operations_keep_actor_time_before_after_and_filter(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $service = Service::factory()->create();
        $agenda = app(AgendaService::class);
        $booking = app(AppointmentService::class);
        $start = now()->addDays(4)->setTime(10, 0);
        $slot = $agenda->create($admin, $service->id, $start->format('Y-m-d\TH:i'));
        $agenda->toggle($admin, $slot, false);
        $agenda->toggle($admin, $slot, true);
        $target = Slot::factory()->create(['service_id' => $service->id]);
        $a = $booking->book($user, $slot->id, (string) Str::uuid(), 'bot');
        $booking->reschedule($user, $a, $target->id, 'bot');
        $booking->cancel($user, $a->fresh(), 'bot');
        $events = AuditEvent::orderBy('id')->get();
        $this->assertSame(['crear_franja', 'bloquear_franja', 'habilitar_franja', 'reserva', 'reprogramacion', 'cancelacion'], $events->pluck('operation')->all());
        foreach ($events as $i => $event) {
            $this->assertSame($i < 3 ? $admin->id : $user->id, $event->actor_id);
            $this->assertSame($i < 3 ? 'web' : 'bot', $event->channel);
            $this->assertNotNull($event->created_at);
            $this->assertNotEmpty($event->after);
            if (!in_array($event->operation, ['crear_franja', 'reserva'])) {
                $this->assertNotEmpty($event->before);
            }
        }
        $this->assertSame($slot->id, $events[4]->before['slot_id']);
        $this->assertSame($target->id, $events[4]->after['slot_id']);
        $this->actingAs($admin)->get('/admin/auditoria?operation=reprogramacion')->assertInertia(fn (Assert $p) => $p->has('events.data', 1)->where('events.data.0.id', $events[4]->id));
    }

    public function test_rf09_period_limits_bogota_day_service_and_empty_results(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $service = Service::factory()->create();
        foreach ([['2027-03-09 23:59:00', 'CONFIRMADA'], ['2027-03-10 00:00:00', 'CONFIRMADA'], ['2027-03-10 23:59:00', 'CANCELADA'], ['2027-03-11 00:00:00', 'CONFIRMADA']] as [$time, $status]) {
            $start = CarbonImmutable::parse($time, 'America/Bogota');
            $slot = Slot::factory()->create(['service_id' => $service->id, 'starts_at' => $start, 'ends_at' => $start->addMinutes(30)]);
            Appointment::factory()->create(['slot_id' => $slot->id, 'status' => $status]);
        }
        Appointment::factory()->create(['slot_id' => Slot::factory()->create(['starts_at' => '2027-03-10 12:00:00-05:00', 'ends_at' => '2027-03-10 12:30:00-05:00'])->id]);
        $this->actingAs($admin)->get('/admin/reportes?from=2027-03-10&to=2027-03-10&service_id='.$service->id)
            ->assertOk()->assertInertia(fn (Assert $p) => $p->has('byStatus', 2)->where('byService.0.total', 2)->where('byService.0.confirmed', 1)->where('byService.0.cancelled', 1)->where('byDay.0.date', '2027-03-10')->where('byDay.0.total', 2));
        $this->get('/admin/reportes?from=2027-04-01&to=2027-04-02')->assertInertia(fn (Assert $p) => $p->has('byStatus', 0)->has('byService', 0)->has('byDay', 0));
        $this->get('/admin/reportes?from=2027-04-02&to=2027-04-01')->assertSessionHasErrors('to');
    }

    public function test_rf10_reminders_exclude_past_cancelled_and_beyond_24_hours(): void
    {
        $user = User::factory()->create();
        $clock = CarbonImmutable::parse('2027-03-10 08:00:00', 'America/Bogota');
        foreach ([[-1, 'CONFIRMADA'], [0, 'CONFIRMADA'], [24 * 60, 'CONFIRMADA'], [24 * 60 + 1, 'CONFIRMADA'], [60, 'CANCELADA']] as [$offset, $status]) {
            $start = $clock->addMinutes($offset);
            $slot = Slot::factory()->create(['starts_at' => $start, 'ends_at' => $start->addMinutes(30)]);
            Appointment::factory()->create(['user_id' => $user->id, 'slot_id' => $slot->id, 'status' => $status]);
        }
        $reminders = app(ReminderService::class);
        $this->assertSame(2, $reminders->emit($user, $clock));
        $this->assertSame(0, $reminders->emit($user, $clock));
        $this->assertSame(2, SimulatedMessage::where('type', 'reminder')->count());
    }

    public function test_rf10_menu_during_patient_identification_should_restart_guidance(): void
    {
        $this->post('/bot', ['message' => 'CC'])->assertRedirect('/bot');
        $this->post('/bot', ['message' => 'menu'])->assertRedirect('/bot');
        $this->get('/bot')->assertInertia(fn (Assert $p) => $p->where('onboarding.label', 'Tipo de documento'));
        $this->assertDatabaseCount('users', 0);
    }

    public function test_rf10_new_session_should_not_offer_confirmation_from_old_session(): void
    {
        $user = app(PatientIdentityService::class)->create('Paciente Auditoria', 'CC', 'LABAUDIT001', 'Auditoria!123', true);
        $slot = Slot::factory()->create();
        $bot = app(BotService::class);
        $state = [];
        foreach (['1', 'service:'.$slot->service_id, 'date:'.$slot->starts_at->toDateString(), 'slot:'.$slot->id] as $input) {
            $state = $bot->respond($user, $input, $state);
        }
        $this->assertSame('confirm', $state['step']);
        // A new authenticated web session opens the public bot without booking state.
        $this->actingAs($user)->get('/bot')->assertOk();
        $this->assertNull(session('bot'));
        $latest = SimulatedMessage::where('user_id', $user->id)->where('sender', 'bot')->where('type', 'conversation')->latest('id')->firstOrFail();
        $this->assertContains('1', array_column($latest->options, 'value'), 'A new session should display its current menu rather than stale confirmation choices.');
    }

    public function test_rf01_document_login_throttle_should_use_visible_document_error_field(): void
    {
        $user = app(PatientIdentityService::class)->create('Paciente Auditoria', 'CC', 'LABAUDIT002', 'Auditoria!123', true);
        for ($i = 0; $i < 6; $i++) {
            $this->post('/acceso', ['document_type' => 'CC', 'document_number' => 'LABAUDIT002', 'password' => 'wrong'])->assertSessionHasErrors('document_number');
        }
        $this->post('/acceso', ['document_type' => 'CC', 'document_number' => 'LABAUDIT002', 'password' => 'wrong'])->assertSessionHasErrors('document_number');
        $this->assertGuest();
    }
}
