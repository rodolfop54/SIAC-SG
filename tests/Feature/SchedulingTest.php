<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AuditEvent;
use App\Models\Service;
use App\Models\SimulatedMessage;
use App\Models\Slot;
use App\Models\User;
use App\Services\AppointmentService;
use App\Services\AuditService;
use App\Services\BotService;
use App\Services\ReminderService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SchedulingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function user(): User
    {
        return User::factory()->create();
    }

    private function slot(array $attrs = []): Slot
    {
        return Slot::factory()->create($attrs);
    }

    private function book(User $u, Slot $s, string $channel = 'web'): Appointment
    {
        return app(AppointmentService::class)->book($u, $s->id, (string) Str::uuid(), $channel);
    }

    public function test_t01_login_invalid_valid_and_logout(): void
    {
        $u = User::factory()->create(['password' => 'Test!pass123']);
        $this->post('/acceso', ['alias' => $u->alias, 'password' => 'wrong'])->assertSessionHasErrors('alias');
        $this->assertGuest();
        $this->post('/acceso', ['alias' => $u->alias, 'password' => 'Test!pass123'])->assertRedirect('/disponibilidad');
        $this->assertAuthenticatedAs($u);
        $this->assertTrue(Hash::check('Test!pass123', $u->password));
        $this->post('/salir')->assertRedirect('/acceso?ended=1');
        $this->assertGuest();
    }

    public function test_t02_availability_excludes_occupied_disabled_past_and_disabled_service(): void
    {
        $u = $this->user();
        $service = Service::factory()->create();
        $start = now()->addDays(2)->setTime(9, 0);
        $free = $this->slot(['service_id' => $service->id, 'starts_at' => $start, 'ends_at' => $start->copy()->addMinutes(30)]);
        $occupied = $this->slot(['service_id' => $service->id, 'starts_at' => $start->copy()->addHour(), 'ends_at' => $start->copy()->addMinutes(90)]);
        $this->book($u, $occupied);
        $this->slot(['service_id' => $service->id, 'enabled' => false, 'starts_at' => $start->copy()->addHours(2), 'ends_at' => $start->copy()->addMinutes(150)]);
        $this->slot(['service_id' => $service->id, 'starts_at' => now()->subDay(), 'ends_at' => now()->subDay()->addMinutes(30)]);
        $this->actingAs($u)->get('/disponibilidad?service_id='.$service->id.'&date='.$start->toDateString())->assertOk()->assertInertia(fn (Assert $p) => $p->component('Availability')->has('slots', 1)->where('slots.0.id', $free->id));
        $service->update(['enabled' => false]);
        $this->assertFalse(Slot::available()->whereKey($free->id)->exists());
    }

    public function test_t03_reserve_persists_and_retry_returns_same_result(): void
    {
        $u = $this->user();
        $slot = $this->slot();
        $key = (string) Str::uuid();
        $payload = ['slot_id' => $slot->id, 'request_key' => $key, 'confirmed' => true];
        $this->actingAs($u)->post('/citas', $payload)->assertRedirect();
        $this->post('/citas', $payload)->assertRedirect();
        $this->assertDatabaseCount('appointments', 1);
        $this->assertDatabaseCount('audit_events', 1);
        $this->assertFalse(Slot::available()->whereKey($slot->id)->exists());
    }

    public function test_t04_only_own_history_and_confirmation(): void
    {
        $u = $this->user();
        $a = $this->book($u, $this->slot());
        $this->book($this->user(), $this->slot());
        $this->actingAs($u)->get('/mis-citas')->assertInertia(fn (Assert $p) => $p->has('appointments.data', 1)->where('appointments.data.0.code', $a->code));
        $this->get('/citas/'.$a->id)->assertOk();
    }

    public function test_t05_conflict_keeps_original_then_reschedule_preserves_code(): void
    {
        $u = $this->user();
        $s = $this->slot();
        $a = $this->book($u, $s);
        $target = $this->slot(['service_id' => $s->service_id]);
        $occupied = $this->book($this->user(), $target);
        $this->actingAs($u)->patch('/citas/'.$a->id, ['slot_id' => $target->id, 'confirmed' => true])->assertSessionHasErrors('conflict');
        $this->assertSame($s->id, $a->fresh()->slot_id);
        $this->assertDatabaseCount('audit_events', 2);
        app(AppointmentService::class)->cancel($occupied->user, $occupied);
        $this->patch('/citas/'.$a->id, ['slot_id' => $target->id, 'confirmed' => true])->assertRedirect('/citas/'.$a->id);
        $this->assertSame($a->code, $a->fresh()->code);
        $this->assertSame($target->id, $a->fresh()->slot_id);
    }

    public function test_t06_cancel_requires_confirmation_and_is_idempotent(): void
    {
        $u = $this->user();
        $a = $this->book($u, $this->slot());
        $this->actingAs($u)->post('/citas/'.$a->id.'/cancelar', [])->assertSessionHasErrors('confirmed');
        for ($i = 0; $i < 2; $i++) {
            $this->post('/citas/'.$a->id.'/cancelar', ['confirmed' => true])->assertRedirect('/mis-citas');
        }
        $this->assertSame('CANCELADA', $a->fresh()->status);
        $this->assertTrue(Slot::available()->whereKey($a->slot_id)->exists());
        $this->assertSame(1, AuditEvent::where('operation', 'cancelacion')->count());
    }

    public function test_t07_admin_creates_blocks_enables_but_cannot_block_occupied_or_overlap(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $service = Service::factory()->create();
        $time = now()->addDays(3)->setTime(10, 0);
        $d = ['service_id' => $service->id, 'starts_at' => $time->format('Y-m-d\TH:i')];
        $this->actingAs($admin)->post('/admin/franjas', $d)->assertRedirect();
        $s = Slot::first();
        $this->post('/admin/franjas', $d)->assertSessionHasErrors('starts_at');
        $this->patch('/admin/franjas/'.$s->id, ['enabled' => false])->assertRedirect();
        $this->assertFalse($s->fresh()->enabled);
        $this->patch('/admin/franjas/'.$s->id, ['enabled' => true])->assertRedirect();
        $this->book($this->user(), $s->fresh());
        $this->patch('/admin/franjas/'.$s->id, ['enabled' => false])->assertSessionHasErrors('conflict');
        $this->assertTrue($s->fresh()->enabled);
    }

    public function test_t08_audit_before_after_and_actor_without_secrets(): void
    {
        $u = $this->user();
        $a = $this->book($u, $this->slot(), 'bot');
        app(AppointmentService::class)->cancel($u, $a, 'bot');
        $e = AuditEvent::latest('id')->first();
        $this->assertSame($u->id, $e->actor_id);
        $this->assertSame('CONFIRMADA', $e->before['status']);
        $this->assertSame('CANCELADA', $e->after['status']);
        $this->assertSame('bot', $e->channel);
        $this->assertStringNotContainsString('password', $e->toJson());
    }

    public function test_t09_reports_match_database_and_filter_by_service(): void
    {
        $u = $this->user();
        $s = $this->slot();
        $a = $this->book($u, $s);
        $b = $this->book($u, $this->slot(['service_id' => $s->service_id]));
        app(AppointmentService::class)->cancel($u, $b);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/admin/reportes?from='.now()->toDateString().'&to='.now()->addYears(2)->toDateString().'&service_id='.$s->service_id)->assertInertia(fn (Assert $p) => $p->component('Reports')->has('byStatus', 2)->where('byService.0.total', 2)->where('byService.0.confirmed', 1)->where('byService.0.cancelled', 1));
    }

    public function test_t10_bot_full_lifecycle_invalid_input_and_unique_reminders(): void
    {
        $u = $this->user();
        $s = $this->slot(['starts_at' => now()->addHours(2), 'ends_at' => now()->addHours(3)]);
        $target = $this->slot(['service_id' => $s->service_id]);
        $bot = app(BotService::class);
        $state = [];
        $state = $bot->respond($u, 'invalid text', $state);
        $this->assertDatabaseCount('appointments', 0);
        foreach (['1', 'service:'.$s->service_id, 'date:'.$s->starts_at->toDateString(), 'slot:'.$s->id, 'confirmar', 'confirmar'] as $input) {
            $state = $bot->respond($u, $input, $state);
        }
        $this->assertDatabaseCount('appointments', 1);
        $a = Appointment::first();
        $this->assertSame('bot', $a->channel);
        $reminders = app(ReminderService::class);
        $this->assertSame(1, $reminders->emit($u, now()));
        $this->assertSame(0, $reminders->emit($u, now()));
        foreach (['3', 'appointment:'.$a->id, 'date:'.$target->starts_at->toDateString(), 'slot:'.$target->id, 'confirmar'] as $input) {
            $state = $bot->respond($u, $input, $state);
        }
        $this->assertSame($target->id, $a->fresh()->slot_id);
        foreach (['4', 'appointment:'.$a->id, 'confirmar', 'confirmar'] as $input) {
            $state = $bot->respond($u, $input, $state);
        }
        $this->assertSame('CANCELADA', $a->fresh()->status);
        $this->assertSame(1, AuditEvent::where('operation', 'cancelacion')->count());
        $this->actingAs($u)->get('/mis-citas')->assertInertia(fn (Assert $p) => $p->where('appointments.data.0.status', 'CANCELADA'));
        $state = $bot->respond($u, '0', $state);
        $this->assertSame('ended', $state['step']);
    }

    public function test_t14_all_foreign_mutations_and_admin_routes_are_forbidden(): void
    {
        $a = $this->book($this->user(), $this->slot());
        $u = $this->user();
        $this->actingAs($u);
        $this->get('/citas/'.$a->id)->assertForbidden();
        $this->patch('/citas/'.$a->id, ['slot_id' => $a->slot_id, 'confirmed' => true])->assertForbidden();
        $this->post('/citas/'.$a->id.'/cancelar', ['confirmed' => true])->assertForbidden();
        $this->get('/disponibilidad?appointment='.$a->id)->assertForbidden();
        foreach (['/admin/agenda', '/admin/auditoria', '/admin/reportes'] as $url) {
            $this->get($url)->assertForbidden();
        }
        $this->post('/admin/franjas', ['service_id' => $a->slot->service_id, 'starts_at' => now()->addDays(3)->format('Y-m-d\TH:i')])->assertForbidden();
        $this->patch('/admin/franjas/'.$a->slot_id, ['enabled' => false])->assertForbidden();
    }

    public function test_t15_validation_idle_session_and_role_forgery(): void
    {
        $u = $this->user();
        $this->actingAs($u)->post('/citas', ['slot_id' => 'bad', 'request_key' => 'invalid', 'role' => 'admin'])->assertSessionHasErrors(['slot_id', 'request_key', 'confirmed']);
        $this->assertSame('user', $u->fresh()->role);
        $this->assertDatabaseCount('appointments', 0);
        $this->withSession(['last_activity' => now()->subMinutes(16)->timestamp])->get('/mis-citas')->assertRedirect('/acceso?ended=1');
        $this->assertGuest();
    }

    public function test_t16_audit_failure_rolls_back_booking(): void
    {
        $u = $this->user();
        $s = $this->slot();
        $audit = \Mockery::mock(AuditService::class);
        $audit->shouldReceive('record')->once()->andThrow(new \RuntimeException('Injected failure'));
        try {
            (new AppointmentService($audit))->book($u, $s->id, (string) Str::uuid());
            $this->fail('Expected rollback');
        } catch (\RuntimeException $e) {
            $this->assertSame('Injected failure', $e->getMessage());
        }
        $this->assertDatabaseCount('appointments', 0);
        $this->assertTrue(Slot::available()->whereKey($s->id)->exists());
    }

    public function test_t16_postgres_partial_index_rejects_duplicate_active_slot(): void
    {
        $a = $this->book($this->user(), $this->slot());
        $this->expectException(UniqueConstraintViolationException::class);
        DB::transaction(fn () => Appointment::factory()->create(['slot_id' => $a->slot_id]));
    }

    public function test_t17_only_synthetic_fields_and_local_messages(): void
    {
        $this->assertFalse(Schema::hasColumn('users', 'phone'));
        $this->assertTrue(Schema::hasColumn('users', 'document_number'));
        $u = $this->user();
        $bot = app(BotService::class);
        $bot->respond($u, 'Mi documento real es 12345', []);
        $this->assertFalse(SimulatedMessage::where('content', 'like', '%12345%')->exists());
    }

    public function test_t10_reminder_only_history_still_has_identification_prompt(): void
    {
        $u = $this->user();
        SimulatedMessage::create(['user_id' => $u->id, 'sender' => 'bot', 'type' => 'reminder', 'content' => 'Recordatorio sintético', 'event_key' => 'test-reminder']);
        $this->actingAs($u)->get('/bot')->assertInertia(fn (Assert $p) => $p->has('messages', 2)->where('messages.1.type', 'conversation')->where('current.options.0.value', '1'));
    }

    public function test_t10_invalid_structured_options_recover_without_mutation(): void
    {
        $u = $this->user();
        $s = $this->slot();
        $bot = app(BotService::class);
        foreach ([['step' => 'date', 'mode' => 'book', 'service_id' => $s->service_id], ['step' => 'service', 'mode' => 'book'], ['step' => 'appointment', 'mode' => 'cancel']] as $i => $state) {
            $result = $bot->respond($u, ['date:2026-99-99', 'service:999999', 'appointment:999999'][$i], $state);
            $this->assertSame('menu', $result['step']);
        }
        $foreign = $this->book($this->user(), $s);
        $bot->respond($u, 'appointment:'.$foreign->id, ['step' => 'appointment', 'mode' => 'cancel']);
        $this->assertSame('CONFIRMADA', $foreign->fresh()->status);
        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_t15_failed_login_is_limited_without_limiting_valid_sessions(): void
    {
        $u = User::factory()->create(['password' => 'Test!12345']);
        for ($i = 0; $i < 7; $i++) {
            $this->post('/acceso', ['alias' => $u->alias, 'password' => 'wrong'])->assertSessionHasErrors('alias');
        }
        $this->assertGuest();
        $this->assertStringContainsString('Demasiados', session('errors')->first('alias'));
        $this->travel(61)->seconds();
        $this->post('/acceso', ['alias' => $u->alias, 'password' => 'Test!12345'])->assertRedirect('/disponibilidad');
        $this->assertAuthenticatedAs($u);
    }
}
