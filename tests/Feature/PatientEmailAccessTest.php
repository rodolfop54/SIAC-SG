<?php

namespace Tests\Feature;

use App\Models\PatientAccessChallenge;
use App\Models\Slot;
use App\Models\User;
use App\Services\PatientIdentityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PatientEmailAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['patient_access.enabled' => true, 'patient_access.brevo_key' => 'test-key', 'patient_access.from_address' => 'sender@example.test']);
        Http::preventStrayRequests();
        Http::fake(['api.brevo.com/*' => Http::response(['messageId' => 'test-delivery'], 201)]);
    }

    private function code(): string
    {
        $request = Http::recorded()->last()[0];
        preg_match('/\b\d{6}\b/', $request['textContent'], $matches);

        return $matches[0];
    }

    private function signup(string $number = 'LABEMAIL001'): array
    {
        return ['document_type' => 'CC', 'document_number' => $number, 'name' => 'Paciente Correo Ficticio', 'email' => 'patient@example.test'];
    }

    public function test_bot_creates_shared_account_only_after_email_verification_and_keeps_reservation_on_web(): void
    {
        foreach (['CC', 'LABEMAIL001', 'Paciente Correo Ficticio', 'patient@example.test'] as $message) {
            $this->post('/bot', compact('message'))->assertRedirect('/bot')->assertSessionHasNoErrors();
        }
        $this->assertDatabaseCount('users', 0);
        $code = $this->code();
        $this->get('/bot')->assertInertia(fn (Assert $p) => $p->where('patient', null)->where('onboarding.label', 'Código de acceso'));
        $this->post('/bot', ['message' => $code])->assertRedirect('/bot')->assertSessionHasNoErrors();
        $patient = User::firstOrFail();
        $this->assertNotNull($patient->email_verified_at);
        $this->assertNotNull($patient->web_registered_at);
        $this->assertNull($patient->password_set_at);
        $this->assertNotSame('patient@example.test', DB::table('users')->value('email'));
        $this->assertStringNotContainsString($code, DB::table('patient_access_challenges')->value('token_hash'));
        $slot = Slot::factory()->create();
        foreach (['1', 'service:'.$slot->service_id, 'date:'.$slot->starts_at->toDateString(), 'slot:'.$slot->id, 'confirmar'] as $message) {
            $this->post('/bot', compact('message'))->assertRedirect();
        }
        $this->post('/bot/abrir-web')->assertRedirect('/mis-citas');
        $this->assertAuthenticatedAs($patient);
        $this->assertDatabaseCount('users', 1);
        $this->get('/mis-citas')->assertInertia(fn (Assert $p) => $p->where('appointments.data.0.user_id', $patient->id));
    }

    public function test_code_expires_is_single_use_and_cannot_reset_a_password(): void
    {
        $this->post('/acceso/codigo', $this->signup())->assertRedirect('/acceso/codigo');
        $code = $this->code();
        $id = session('patient_email_challenge');
        $this->post('/recuperar/confirmar', ['id' => $id, 'token' => $code, 'password' => 'NewSecret!123', 'password_confirmation' => 'NewSecret!123'])->assertSessionHasErrors('code');
        $this->post('/acceso/codigo/verificar', compact('code'))->assertRedirect('/mis-citas');
        $this->post('/salir');
        $this->withSession(['patient_email_challenge' => $id])->post('/acceso/codigo/verificar', compact('code'))->assertSessionHasErrors('code');
        $this->assertGuest();
        $this->travel(61)->seconds();
        $this->post('/acceso/codigo', $this->signup())->assertRedirect('/acceso/codigo');
        $code = $this->code();
        $this->travel(11)->minutes();
        $this->post('/acceso/codigo/verificar', compact('code'))->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_five_wrong_guesses_lock_the_challenge_without_changing_accounts(): void
    {
        $this->post('/acceso/codigo', $this->signup())->assertRedirect();
        $code = $this->code();
        $wrong = $code === '111111' ? '222222' : '111111';
        for ($i = 0; $i < 5; $i++) {
            $this->post('/acceso/codigo/verificar', ['code' => $wrong])->assertSessionHasErrors('code');
        }
        $this->post('/acceso/codigo/verificar', compact('code'))->assertSessionHasErrors('code');
        $this->assertDatabaseCount('users', 0);
        $this->assertSame(5, PatientAccessChallenge::first()->attempts);
    }

    public function test_existing_account_cannot_be_claimed_by_providing_a_different_email(): void
    {
        $patient = app(PatientIdentityService::class)->create('Paciente Original', 'CC', 'LABEMAIL001', 'OldPassword!123');
        $this->post('/acceso/codigo', $this->signup())->assertSessionHasErrors('current_password');
        Http::assertNothingSent();
        $this->travel(61)->seconds();
        $this->post('/acceso/codigo', [...$this->signup(), 'current_password' => 'OldPassword!123'])->assertRedirect();
        $this->post('/acceso/codigo/verificar', ['code' => $this->code()])->assertRedirect('/mis-citas');
        $this->assertSame($patient->id, auth()->id());
        $this->assertTrue(Hash::check('OldPassword!123', $patient->fresh()->password));
        $this->post('/salir');
        $this->travel(61)->seconds();
        $this->post('/acceso/codigo', [...$this->signup(), 'email' => 'attacker@example.test'])->assertRedirect();
        $last = Http::recorded()->last()[0];
        $this->assertSame('patient@example.test', $last['to'][0]['email']);
        $this->assertSame('patient@example.test', $patient->fresh()->email);
    }

    public function test_password_reset_link_is_single_use_invalidates_old_codes_and_sessions_and_keeps_identity(): void
    {
        $this->post('/acceso/codigo', $this->signup())->assertRedirect();
        $this->post('/acceso/codigo/verificar', ['code' => $this->code()])->assertRedirect();
        $patient = User::firstOrFail();
        $this->travel(61)->seconds();
        $this->post('/acceso/codigo', $this->signup())->assertRedirect();
        $oldCode = $this->code();
        $oldId = session('patient_email_challenge');
        $this->post('/recuperar', ['document_type' => 'CC', 'document_number' => 'LABEMAIL001'])->assertSessionHasNoErrors();
        $request = Http::recorded()->last()[0];
        preg_match('~/recuperar/([^/]+)/([A-Za-z0-9]+)~', $request['textContent'], $matches);
        $payload = ['id' => $matches[1], 'token' => $matches[2], 'password' => 'NewPassword!123', 'password_confirmation' => 'NewPassword!123'];
        $this->post('/recuperar/confirmar', $payload)->assertRedirect('/acceso');
        $this->assertGuest();
        $this->assertSame(1, $patient->fresh()->auth_version);
        $this->assertTrue(Hash::check('NewPassword!123', $patient->fresh()->password));
        $this->post('/recuperar/confirmar', $payload)->assertSessionHasErrors('code');
        $this->withSession(['patient_email_challenge' => $oldId])->post('/acceso/codigo/verificar', ['code' => $oldCode])->assertSessionHasErrors('code');
        $this->withSession(['bot_patient_id' => $patient->id, 'bot_last_activity' => now()->timestamp, 'bot_auth_version' => 0])->get('/bot')->assertInertia(fn (Assert $p) => $p->where('patient', null));
        $this->post('/acceso', ['document_type' => 'CC', 'document_number' => 'LABEMAIL001', 'password' => 'NewPassword!123'])->assertRedirect('/disponibilidad');
        $this->assertAuthenticatedAs($patient->fresh());
        $this->assertDatabaseCount('users', 1);
    }

    public function test_mail_failure_never_creates_an_authenticated_profile_or_a_usable_challenge(): void
    {
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['api.brevo.com/*' => Http::response([], 503)]);
        $this->post('/acceso/codigo', $this->signup())->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
        $this->assertNotNull(PatientAccessChallenge::first()->consumed_at);
    }

    public function test_recovery_for_unknown_document_has_generic_response_and_sends_no_email(): void
    {
        $this->post('/recuperar', ['document_type' => 'CC', 'document_number' => 'LABUNKNOWN001'])
            ->assertSessionHas('success', 'Si el documento tiene un correo verificado, recibirás un enlace para crear o cambiar la contraseña.');
        Http::assertNothingSent();
        $this->assertDatabaseCount('patient_access_challenges', 0);
    }

    public function test_returning_bot_patient_receives_code_at_verified_email_without_registering_again(): void
    {
        $this->post('/acceso/codigo', $this->signup())->assertSessionHasNoErrors();
        $this->post('/acceso/codigo/verificar', ['code' => $this->code()])->assertRedirect('/mis-citas');
        $patient = User::firstOrFail();
        $this->post('/salir');
        $this->travel(61)->seconds();
        foreach (['CC', 'LABEMAIL001'] as $message) {
            $this->post('/bot', compact('message'))->assertSessionHasNoErrors();
        }
        $this->get('/bot')->assertInertia(fn (Assert $p) => $p->where('patient', null)->where('onboarding.label', 'Código de acceso'));
        $this->post('/bot', ['message' => $this->code()])->assertSessionHasNoErrors();
        $this->assertSame($patient->id, session('bot_patient_id'));
        $this->assertDatabaseCount('users', 1);
    }

    public function test_expired_or_revoked_bot_session_cannot_enroll_a_legacy_account(): void
    {
        $patient = app(PatientIdentityService::class)->create('Paciente Original', 'CC', 'LABEMAIL001', 'OldPassword!123');
        $patient->forceFill(['auth_version' => 1])->save();
        $this->withSession(['bot_patient_id' => $patient->id, 'bot_last_activity' => now()->timestamp, 'bot_auth_version' => 0])
            ->post('/acceso/codigo', $this->signup())->assertSessionHasErrors('current_password');
        $this->travel(61)->seconds();
        $this->withSession(['bot_patient_id' => $patient->id, 'bot_last_activity' => now()->subMinutes(16)->timestamp, 'bot_auth_version' => 1])
            ->post('/acceso/codigo', $this->signup())->assertSessionHasErrors('current_password');
        Http::assertNothingSent();
        $this->assertNull($patient->fresh()->email_verified_at);
    }

    public function test_changing_password_revokes_older_web_sessions_and_reset_page_does_not_leak_link(): void
    {
        $patient = User::factory()->create(['auth_version' => 1]);
        $this->actingAs($patient)->withSession(['auth_version' => 0, 'last_activity' => now()->timestamp])
            ->get('/mis-citas')->assertRedirect('/acceso?ended=1');
        $this->assertGuest();
        $this->get('/recuperar/123e4567-e89b-42d3-a456-426614174000/testtoken')
            ->assertOk()->assertHeader('Referrer-Policy', 'no-referrer')->assertHeader('Cache-Control', 'no-store, private');
        $this->post('/bot/abrir-web')->assertForbidden();
    }

    public function test_resend_is_limited_and_no_secret_is_flashed_on_validation_errors(): void
    {
        $this->post('/acceso/codigo', $this->signup())->assertSessionHasNoErrors();
        $this->post('/acceso/codigo', $this->signup())->assertSessionHasErrors('email');
        Http::assertSentCount(1);
        $this->post('/recuperar/confirmar', ['id' => 'invalid', 'token' => 'private-token', 'password' => 'private-password', 'password_confirmation' => 'wrong'])
            ->assertSessionHasErrors('id')->assertSessionMissing('_old_input.token')->assertSessionMissing('_old_input.password');
    }
}
