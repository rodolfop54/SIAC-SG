<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\SimulatedMessage;
use App\Models\Slot;
use App\Models\User;
use App\Services\BotService;
use App\Services\PatientIdentityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BotSessionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function identify(): User
    {
        foreach (['CC', 'LABSESSION001', 'Paciente Sintetico', 'ClavePrueba!123', 'ClavePrueba!123'] as $message) {
            $this->post('/bot', compact('message'))->assertRedirect('/bot');
        }

        return User::whereNotNull('document_key')->firstOrFail();
    }

    public function test_global_commands_work_in_every_identification_step_without_creating_a_patient(): void
    {
        $secret = Hash::make('ClavePrueba!123');
        foreach (['document_type', 'document_number', 'name', 'new_secret', 'confirm_secret', 'existing_secret', 'ended'] as $step) {
            foreach (['menú', 'menu', '0', ' SALIR '] as $message) {
                $this->withSession(['bot_identity' => ['step' => $step, 'document_type' => 'CC', 'document_number' => 'LABSESSION001', 'name' => 'Paciente Sintetico', 'secret_hash' => $secret]])
                    ->post('/bot', compact('message'))->assertRedirect('/bot')->assertSessionHasNoErrors();
                $ended = in_array(trim($message), ['0', 'SALIR']);
                $this->get('/bot')->assertInertia(fn (Assert $p) => $p->where('patient', null)->where('current', null)->has('messages', 0)->where('onboarding.label', $ended ? 'Mensaje al asistente' : 'Tipo de documento'));
                $this->assertNull(session('bot_identity.secret_hash'));
                $this->assertNull(session('bot_patient_id'));
            }
        }
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('appointments', 0);
        $this->assertDatabaseCount('simulated_messages', 0);
    }

    public function test_exit_hides_identity_and_history_even_when_web_account_remains_logged_in(): void
    {
        $patient = $this->identify();
        $this->actingAs($patient)->post('/bot', ['message' => 'salir'])->assertRedirect('/bot');
        $this->assertAuthenticatedAs($patient);
        $this->get('/bot')->assertInertia(fn (Assert $p) => $p->where('patient', null)->where('current', null)->has('messages', 0));
        $this->post('/bot', ['message' => 'menu']);
        foreach (['CC', 'LABSESSION001'] as $message) {
            $this->post('/bot', compact('message'));
        }
        $this->get('/bot')->assertInertia(fn (Assert $p) => $p->where('patient', null)->where('onboarding.inputType', 'password'));
        $this->post('/bot', ['message' => 'incorrecta'])->assertSessionHasErrors('current_password');
        $this->post('/bot', ['message' => 'ClavePrueba!123'])->assertRedirect('/bot');
        $this->get('/bot')->assertInertia(fn (Assert $p) => $p->where('patient.name', $patient->name)->where('current.options.0.value', '1'));
    }

    public function test_other_conversation_and_reminder_cannot_replace_current_session_choices(): void
    {
        $patient = $this->identify();
        $this->post('/bot', ['message' => '1']);
        $state = session('bot');
        // Independent conversation for the same identity writes to the shared transcript.
        app(BotService::class)->respond($patient, '4', ['step' => 'menu']);
        SimulatedMessage::create(['user_id' => $patient->id, 'sender' => 'bot', 'type' => 'reminder', 'content' => 'Recordatorio sintetico', 'event_key' => 'session-regression']);
        $this->get('/bot')->assertInertia(fn (Assert $p) => $p->where('current.message_id', $state['prompt']['message_id'])->where('current.text', $state['prompt']['text'])->where('current.options', $state['prompt']['options']));
    }

    public function test_stale_page_action_is_rejected_without_reservation(): void
    {
        $this->identify();
        $old = session('bot.prompt.message_id');
        $this->post('/bot', ['message' => '1', 'prompt_id' => $old]);
        $this->post('/bot', ['message' => 'confirmar', 'prompt_id' => $old])->assertSessionHasErrors('message');
        $this->assertSame('service', session('bot.step'));
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_invalid_input_clears_pending_confirmation_and_does_not_mutate(): void
    {
        $this->identify();
        $slot = Slot::factory()->create();
        foreach (['1', 'service:'.$slot->service_id, 'date:'.$slot->starts_at->toDateString(), 'slot:'.$slot->id] as $message) {
            $this->post('/bot', compact('message'));
        }
        $this->assertSame('confirm', session('bot.step'));
        $this->post('/bot', ['message' => 'comando desconocido']);
        $this->assertSame('menu', session('bot.step'));
        $this->post('/bot', ['message' => 'confirmar']);
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_numeric_documents_are_rejected_in_bot_registration_and_login(): void
    {
        $this->post('/bot', ['message' => 'CC']);
        $this->post('/bot', ['message' => '123456789'])->assertSessionHasErrors('document_number');
        $this->assertSame('document_number', session('bot_identity.step'));
        $this->post('/registro', ['name' => 'Paciente Sintetico', 'document_type' => 'CC', 'document_number' => '123456789', 'password' => 'ClavePrueba!123', 'password_confirmation' => 'ClavePrueba!123'])->assertSessionHasErrors('document_number');
        $this->post('/acceso', ['document_type' => 'CC', 'document_number' => '123456789', 'password' => 'ClavePrueba!123'])->assertSessionHasErrors('document_number');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_expired_identity_discards_pending_confirmation_and_requires_key_again(): void
    {
        $patient = $this->identify();
        $this->withSession(['bot_last_activity' => now()->subMinutes(16)->timestamp])->get('/bot')
            ->assertInertia(fn (Assert $p) => $p->where('patient', null)->where('current', null)->has('messages', 0));
        $this->assertNull(session('bot'));
        foreach (['CC', 'LABSESSION001'] as $message) {
            $this->post('/bot', compact('message'));
        }
        $this->get('/bot')->assertInertia(fn (Assert $p) => $p->where('patient', null)->where('onboarding.inputType', 'password'));
        $this->assertSame(0, Appointment::where('user_id', $patient->id)->count());
    }

    public function test_existing_profile_key_is_rate_limited_and_recovers_after_wait(): void
    {
        app(PatientIdentityService::class)->create('Paciente Sintetico', 'CC', 'LABLIMIT001', 'ClavePrueba!123');
        foreach (['CC', 'LABLIMIT001'] as $message) {
            $this->post('/bot', compact('message'));
        }
        for ($i = 0; $i < 5; $i++) {
            $this->post('/bot', ['message' => 'incorrecta'])->assertSessionHasErrors('current_password');
        }
        $this->post('/bot', ['message' => 'ClavePrueba!123'])->assertSessionHasErrors('current_password');
        $this->assertStringContainsString('Demasiados', session('errors')->first('current_password'));
        $this->get('/bot')->assertInertia(fn (Assert $p) => $p->where('patient', null)->has('messages', 0));
        $this->travel(61)->seconds();
        $this->post('/bot', ['message' => 'ClavePrueba!123'])->assertRedirect('/bot')->assertSessionHasNoErrors();
        $this->get('/bot')->assertInertia(fn (Assert $p) => $p->where('patient.name', 'Paciente Sintetico'));
    }

    public function test_changed_actor_cannot_reuse_another_patients_pending_state(): void
    {
        $first = $this->identify();
        $state = session('bot');
        $other = User::factory()->create();
        $this->withSession(['bot_patient_id' => $other->id, 'bot_actor_id' => $first->id, 'bot' => $state])->get('/bot')
            ->assertInertia(fn (Assert $p) => $p->where('patient.name', $other->name)->where('current.options.0.value', '1'));
        $this->assertSame($other->id, session('bot_actor_id'));
        $this->assertNotSame($state['prompt']['message_id'], session('bot.prompt.message_id'));
    }
}
