<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\SimulatedMessage;
use App\Models\Slot;
use App\Models\User;
use App\Services\PatientIdentityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PatientAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function identify(string $document = 'FICT900001'): User
    {
        foreach (['CC', $document, 'Paciente Ficticio', 'ClaveFicticia!123', 'ClaveFicticia!123'] as $input) {
            $this->post('/bot', ['message' => $input])->assertRedirect('/bot');
        }

        return User::whereNotNull('document_key')->firstOrFail();
    }

    private function registration(array $extra = []): array
    {
        return [...['name' => 'Paciente Ficticio', 'document_type' => 'CC', 'document_number' => 'FICT900001', 'password' => 'NuevaClave!123', 'password_confirmation' => 'NuevaClave!123'], ...$extra];
    }

    public function test_public_bot_creates_patient_reserves_and_registration_keeps_same_identity_and_appointments(): void
    {
        $this->get('/bot')->assertOk()->assertInertia(fn (Assert $p) => $p->where('patient', null)->has('onboarding.options', 11)->has('messages', 0));
        $patient = $this->identify();
        $this->assertGuest();
        $this->assertNotNull($patient->web_registered_at);
        $this->assertSame('CC', $patient->document_type);
        $this->assertSame('FICT900001', $patient->document_number);
        $this->assertNotSame('FICT900001', DB::table('users')->where('id', $patient->id)->value('document_number'));
        $this->assertStringNotContainsString('document_number', $patient->toJson());
        $slot = Slot::factory()->create();
        foreach (['1', 'service:'.$slot->service_id, 'date:'.$slot->starts_at->toDateString(), 'slot:'.$slot->id, 'confirmar'] as $input) {
            $this->post('/bot', ['message' => $input])->assertRedirect();
        }
        $appointment = Appointment::firstOrFail();
        $this->assertSame($patient->id, $appointment->user_id);
        $this->assertSame('bot', $appointment->channel);
        $this->post('/bot/abrir-web')->assertRedirect('/mis-citas');
        $this->assertAuthenticatedAs($patient);
        $this->assertDatabaseCount('users', 1);
        $this->assertNotNull($patient->fresh()->web_registered_at);
        $this->assertSame('user', $patient->fresh()->role);
        $this->get('/mis-citas')->assertInertia(fn (Assert $p) => $p->where('appointments.data.0.id', $appointment->id));
        $this->assertFalse(SimulatedMessage::where('content', 'like', '%FICT900001%')->orWhere('content', 'like', '%ClaveFicticia%')->exists());
    }

    public function test_document_alone_cannot_reveal_patient_or_claim_existing_profile(): void
    {
        $identity = app(PatientIdentityService::class);
        $patient = $identity->create('Nombre protegido', 'CC', 'FICT900001', 'ClaveFicticia!123');
        $patient->forceFill(['web_registered_at' => null])->save();
        foreach (['CC', 'FICT900001'] as $input) {
            $this->post('/bot', ['message' => $input])->assertRedirect();
        }
        $this->get('/bot')->assertInertia(fn (Assert $p) => $p->where('patient', null)->has('messages', 0)->where('onboarding.inputType', 'password'));
        $this->post('/bot', ['message' => 'incorrecta'])->assertSessionHasErrors('current_password');
        $this->assertNull(session('bot_patient_id'));
        $this->post('/registro', $this->registration())->assertSessionHasErrors('current_password');
        $this->assertGuest();
        $this->assertNull($patient->fresh()->web_registered_at);
        $this->assertTrue(Hash::check('ClaveFicticia!123', $patient->fresh()->password));
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_profile_can_be_activated_from_another_session_with_correct_existing_key(): void
    {
        $identity = app(PatientIdentityService::class);
        $patient = $identity->create('Nombre conservado', 'TI', 'FICT900001', 'ClaveFicticia!123');
        $patient->forceFill(['web_registered_at' => null])->save();
        $this->post('/registro', $this->registration(['document_type' => 'TI', 'current_password' => 'ClaveFicticia!123', 'name' => 'Nombre diferente']))->assertRedirect('/mis-citas');
        $this->assertAuthenticatedAs($patient);
        $this->assertSame('Nombre conservado', $patient->fresh()->name);
        $this->assertDatabaseCount('users', 1);
        $this->post('/salir');
        $this->post('/acceso', ['document_type' => 'TI', 'document_number' => 'FICT900001', 'password' => 'NuevaClave!123'])->assertRedirect('/disponibilidad');
        $this->assertAuthenticatedAs($patient);
    }

    public function test_identity_normalizes_format_enforces_uniqueness_and_preserves_document_type(): void
    {
        $identity = app(PatientIdentityService::class);
        $patient = $identity->create('Paciente de prueba', 'CC', 'LAB1.234-567', 'ClaveFicticia!123');
        $this->assertSame($patient->id, $identity->find('CC', 'LAB1234567')->id);
        $this->post('/registro', $this->registration(['document_number' => 'LAB1234567']))->assertSessionHasErrors('document_number');
        $this->assertDatabaseCount('users', 1);
        $other = $identity->create('Otro perfil de prueba', 'PA', 'LAB1234567', 'ClaveFicticia!123', true);
        $this->assertNotSame($patient->id, $other->id);
        $this->assertDatabaseCount('users', 2);
    }

    public function test_bot_account_can_enter_web_with_same_password_and_cannot_use_technical_alias(): void
    {
        $patient = app(PatientIdentityService::class)->create('Paciente de prueba', 'CC', 'FICT900001', 'ClaveFicticia!123');
        $this->post('/acceso', ['document_type' => 'CC', 'document_number' => 'FICT900001', 'password' => 'ClaveFicticia!123'])->assertRedirect('/disponibilidad');
        $this->assertAuthenticatedAs($patient);
        $this->post('/salir');
        $this->post('/acceso', ['alias' => $patient->alias, 'password' => 'ClaveFicticia!123'])->assertSessionHasErrors('alias');
        $this->assertGuest();
    }

    public function test_expired_anonymous_identity_cannot_confirm_and_reset_hides_history(): void
    {
        $patient = $this->identify();
        $this->post('/bot/cambiar-identidad')->assertRedirect('/bot');
        $this->get('/bot')->assertInertia(fn (Assert $p) => $p->where('patient', null)->has('messages', 0));
        $this->withSession(['bot_patient_id' => $patient->id, 'bot_last_activity' => now()->subMinutes(16)->timestamp, 'bot' => ['step' => 'confirm', 'mode' => 'book']])->post('/bot', ['message' => 'confirmar'])->assertSessionHasErrors('message');
        $this->assertDatabaseCount('appointments', 0);
        $this->assertNull(session('bot_patient_id'));
    }

    public function test_direct_web_registration_creates_one_patient_and_duplicate_does_not_change_credentials(): void
    {
        $this->post('/registro', $this->registration())->assertRedirect('/mis-citas');
        $patient = User::firstOrFail();
        $this->assertNotNull($patient->web_registered_at);
        $this->post('/salir');
        $this->post('/registro', $this->registration(['password' => 'OtraClave!123', 'password_confirmation' => 'OtraClave!123']))->assertSessionHasErrors('document_number');
        $this->assertTrue(Hash::check('NuevaClave!123', $patient->fresh()->password));
        $this->assertDatabaseCount('users', 1);
        foreach (['CC', 'FICT900001', 'NuevaClave!123'] as $input) {
            $this->post('/bot', ['message' => $input])->assertRedirect();
        }
        $this->get('/bot')->assertInertia(fn (Assert $p) => $p->where('patient.name', $patient->name)->where('patient.webRegistered', true));
    }
}
