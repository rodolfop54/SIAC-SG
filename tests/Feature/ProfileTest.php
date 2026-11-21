<?php

namespace Tests\Feature;

use App\Models\AuditEvent;
use App\Models\PatientAccessChallenge;
use App\Models\Slot;
use App\Models\User;
use App\Services\AppointmentService;
use App\Services\PatientIdentityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProfileTest extends TestCase
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

    private function patient(): User
    {
        $user = app(PatientIdentityService::class)->create('Paciente Perfil Ficticio', 'CC', 'LABPERFIL001', 'PerfilSecret!123');
        $user->forceFill(['email' => 'original@example.test', 'email_verified_at' => now()])->save();

        return $user;
    }

    private function enter(User $user, bool $recent = true): static
    {
        return $this->actingAs($user)->withSession(['auth_version' => $user->auth_version, 'last_activity' => now()->timestamp,
            'auth_confirmed_at' => $recent ? now()->timestamp : now()->subMinutes(6)->timestamp]);
    }

    private function code(): string
    {
        preg_match('/\b\d{6}\b/', Http::recorded()->last()[0]['textContent'], $matches);

        return $matches[0];
    }

    public function test_profile_and_mutations_require_patient_session(): void
    {
        $this->get('/perfil')->assertRedirect('/acceso?ended=1');
        foreach ([['patch', '/perfil'], ['post', '/perfil/correo'], ['post', '/perfil/correo/verificar'], ['post', '/perfil/correo/cancelar'], ['post', '/perfil/confirmar-acceso'], ['post', '/perfil/confirmar-acceso/verificar']] as [$method, $uri]) {
            $this->{$method}($uri, [])->assertRedirect('/acceso?ended=1');
        }
        $admin = User::factory()->create(['role' => 'admin']);
        $this->enter($admin)->get('/perfil')->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_name_update_only_changes_owner_and_preserves_identity_reservations_and_role(): void
    {
        $user = $this->patient();
        $other = User::factory()->create();
        $slot = Slot::factory()->create();
        $appointment = app(AppointmentService::class)->book($user, $slot->id, (string) Str::uuid());
        $before = $user->getAttributes();
        $this->enter($user)->patch('/perfil', ['name' => 'Nombre Actualizado Ficticio', 'user_id' => $other->id, 'role' => 'admin', 'document_number' => 'LABOTRO001', 'email' => 'attacker@example.test'])->assertSessionHasNoErrors();
        $user->refresh();
        $this->assertSame('Nombre Actualizado Ficticio', $user->name);
        foreach (['id', 'role', 'document_key', 'document_number', 'password', 'email'] as $field) {
            $this->assertSame($before[$field], $user->getAttributes()[$field]);
        }
        $this->assertNotSame($user->name, $other->fresh()->name);
        $this->assertSame($user->id, $appointment->fresh()->user_id);
        $this->get('/perfil')->assertInertia(fn (Assert $p) => $p->where('profile.name', $user->name)->where('auth.user.name', $user->name)->where('profile.document', 'CC · ••••L001'));
        $audit = AuditEvent::where('operation', 'actualizar_perfil')->firstOrFail();
        $this->assertSame(['fields' => ['name']], $audit->after);
        $this->assertStringNotContainsString('original@example.test', $audit->toJson());
        $this->patch('/perfil', ['name' => ''])->assertSessionHasErrors('name');
    }

    public function test_new_email_is_only_activated_after_verification_and_old_sessions_are_revoked(): void
    {
        $user = $this->patient();
        $this->enter($user)->post('/perfil/correo', ['email' => 'new@example.test'])->assertSessionHasNoErrors();
        $id = session('profile_email_challenge');
        $code = $this->code();
        $this->assertSame('original@example.test', $user->fresh()->email);
        $this->get('/perfil')->assertInertia(fn (Assert $p) => $p->where('profile.email', 'original@example.test')->where('pendingEmail', 'new@example.test'));
        $this->assertStringNotContainsString('new@example.test', DB::table('patient_access_challenges')->value('payload'));
        $this->post('/perfil/correo/verificar', compact('code'))->assertRedirect('/perfil')->assertSessionHasNoErrors();
        $this->assertSame('new@example.test', $user->fresh()->email);
        $this->assertSame(1, $user->fresh()->auth_version);
        $this->get('/perfil')->assertOk()->assertSessionHas('auth_version', 1);
        $this->withSession(['profile_email_challenge' => $id])->post('/perfil/correo/verificar', compact('code'))->assertSessionHasErrors('code');
        $this->enter($user->fresh(), false)->withSession(['auth_version' => 0])->get('/perfil')->assertRedirect('/acceso?ended=1');
        $this->assertGuest();
        $this->assertSame(['fields' => ['email'], 'verified' => true], AuditEvent::where('operation', 'actualizar_correo')->firstOrFail()->after);
    }

    public function test_old_session_requires_password_confirmation_before_requesting_change(): void
    {
        $user = $this->patient();
        $this->enter($user, false)->post('/perfil/correo', ['email' => 'new@example.test'])->assertSessionHasErrors('current_password');
        $this->post('/perfil/correo', ['email' => 'new@example.test', 'current_password' => 'WrongSecret!123'])->assertSessionHasErrors('current_password');
        Http::assertNothingSent();
        $this->post('/perfil/correo', ['email' => 'new@example.test', 'current_password' => 'PerfilSecret!123'])->assertSessionHasNoErrors();
        Http::assertSentCount(1);
        $this->assertSame('original@example.test', $user->fresh()->email);
    }

    public function test_passwordless_user_can_confirm_old_email_before_changing_it(): void
    {
        $user = $this->patient();
        $user->forceFill(['password_set_at' => null])->save();
        $this->enter($user, false)->get('/perfil')->assertInertia(fn (Assert $p) => $p->where('profile.hasPassword', false)->where('freshAccess', false));
        $this->post('/perfil/confirmar-acceso')->assertSessionHasNoErrors();
        $code = $this->code();
        $this->assertSame('original@example.test', Http::recorded()->last()[0]['to'][0]['email']);
        $wrong = $code === '111111' ? '222222' : '111111';
        $this->post('/perfil/confirmar-acceso/verificar', ['access_code' => $wrong])->assertSessionHasErrors('access_code')->assertSessionMissing('_old_input.access_code');
        $this->post('/perfil/confirmar-acceso/verificar', ['access_code' => $code])->assertRedirect('/perfil')->assertSessionHasNoErrors();
        $this->post('/perfil/correo', ['email' => 'new@example.test'])->assertSessionHasNoErrors();
        $this->assertSame('new@example.test', Http::recorded()->last()[0]['to'][0]['email']);
        $this->post('/perfil/correo/verificar', ['code' => $this->code()])->assertSessionHasNoErrors();
        $this->assertSame('new@example.test', $user->fresh()->email);
    }

    public function test_wrong_expired_missing_or_other_patient_challenges_cannot_change_email(): void
    {
        $user = $this->patient();
        $this->enter($user)->post('/perfil/correo/verificar', ['code' => '111111'])->assertSessionHasErrors('code');
        $this->post('/perfil/confirmar-acceso/verificar', ['access_code' => '111111'])->assertSessionHasErrors('access_code');
        $this->post('/perfil/correo', ['email' => 'new@example.test'])->assertSessionHasNoErrors();
        $id = session('profile_email_challenge');
        $code = $this->code();
        $other = User::factory()->create();
        $this->enter($other)->withSession(['profile_email_challenge' => $id])->post('/perfil/correo/verificar', compact('code'))->assertSessionHasErrors('code');
        $this->assertSame('original@example.test', $user->fresh()->email);
        $this->enter($user)->withSession(['profile_email_challenge' => $id]);
        $wrong = $code === '111111' ? '222222' : '111111';
        for ($i = 0; $i < 5; $i++) {
            $this->post('/perfil/correo/verificar', ['code' => $wrong])->assertSessionHasErrors('code');
        }
        $this->post('/perfil/correo/verificar', compact('code'))->assertSessionHasErrors('code');
        $this->assertSame(5, PatientAccessChallenge::find($id)->attempts);
        $this->travel(61)->seconds();
        $this->post('/perfil/correo', ['email' => 'later@example.test'])->assertSessionHasNoErrors();
        $code = $this->code();
        $this->travel(11)->minutes();
        $this->post('/perfil/correo/verificar', compact('code'))->assertSessionHasErrors('code');
        $this->assertSame('original@example.test', $user->fresh()->email);
    }

    public function test_cancelled_change_and_login_code_cannot_be_used_to_change_email(): void
    {
        $user = $this->patient();
        $this->enter($user)->post('/perfil/correo', ['email' => 'new@example.test'])->assertSessionHasNoErrors();
        $id = session('profile_email_challenge');
        $code = $this->code();
        $this->post('/perfil/correo/cancelar')->assertSessionHasNoErrors();
        $this->withSession(['profile_email_challenge' => $id])->post('/perfil/correo/verificar', compact('code'))->assertSessionHasErrors('code');
        $this->post('/perfil/confirmar-acceso')->assertSessionHasNoErrors();
        $id = session('profile_access_challenge');
        $this->withSession(['profile_email_challenge' => $id])->post('/perfil/correo/verificar', ['code' => $this->code()])->assertSessionHasErrors('code');
        $this->assertSame('original@example.test', $user->fresh()->email);
    }

    public function test_mail_failure_preserves_existing_email_and_invalidates_pending_code(): void
    {
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['api.brevo.com/*' => Http::response([], 503)]);
        $user = $this->patient();
        $this->enter($user)->post('/perfil/correo', ['email' => 'new@example.test'])->assertSessionHasErrors('email');
        $this->assertSame('original@example.test', $user->fresh()->email);
        $this->assertNotNull(PatientAccessChallenge::firstOrFail()->consumed_at);
        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_email_change_invalidates_previously_requested_login_and_password_recovery_codes(): void
    {
        $user = $this->patient();
        $this->enter($user)->post('/acceso/codigo', ['document_type' => 'CC', 'document_number' => 'LABPERFIL001'])->assertSessionHasNoErrors();
        $oldCode = $this->code();
        $oldId = session('patient_email_challenge');
        $this->post('/recuperar', ['document_type' => 'CC', 'document_number' => 'LABPERFIL001'])->assertSessionHasNoErrors();
        preg_match('~/recuperar/([^/]+)/([A-Za-z0-9]+)~', Http::recorded()->last()[0]['textContent'], $matches);
        $this->post('/perfil/correo', ['email' => 'new@example.test'])->assertSessionHasNoErrors();
        $this->post('/perfil/correo/verificar', ['code' => $this->code()])->assertSessionHasNoErrors();
        $this->post('/salir');
        $this->withSession(['patient_email_challenge' => $oldId])->post('/acceso/codigo/verificar', ['code' => $oldCode])->assertSessionHasErrors('code');
        $this->post('/recuperar/confirmar', ['id' => $matches[1], 'token' => $matches[2], 'password' => 'ChangedSecret!123', 'password_confirmation' => 'ChangedSecret!123'])->assertSessionHasErrors('code');
        $this->assertSame('new@example.test', $user->fresh()->email);
        $this->assertGuest();
    }
}
