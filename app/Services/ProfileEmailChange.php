<?php

namespace App\Services;

use App\Models\PatientAccessChallenge;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProfileEmailChange
{
    public function __construct(private PatientEmailSender $sender, private AuditService $audit) {}

    private function signature(string $id, string $code): string
    {
        return hash_hmac('sha256', $id.':'.$code, config('app.key'));
    }

    public function request(User $user, string $email, string $ip): string
    {
        $email = mb_strtolower(trim($email));
        if ($user->email_verified_at && mb_strtolower($user->email) === $email) {
            throw ValidationException::withMessages(['email' => 'Este ya es tu correo verificado.']);
        }
        $key = 'profile-email:'.$user->id;
        $ipKey = 'patient-email-ip:'.$ip;
        if (RateLimiter::tooManyAttempts($key, 1) || RateLimiter::tooManyAttempts($ipKey, 10)) {
            throw ValidationException::withMessages(['email' => 'Espera un minuto antes de solicitar otro código. Si alcanzaste el límite de correos, vuelve más tarde.']);
        }
        RateLimiter::hit($key, 60);
        RateLimiter::hit($ipKey, 3600);
        $id = (string) Str::uuid();
        $code = (string) random_int(100000, 999999);
        $challenge = PatientAccessChallenge::create([
            'id' => $id, 'user_id' => $user->id, 'document_key' => $user->document_key ?? hash_hmac('sha256', 'profile:'.$user->id, config('app.key')),
            'auth_version' => $user->auth_version, 'purpose' => 'email',
            'payload' => ['email' => $email, 'previous_email' => $user->email],
            'token_hash' => $this->signature($id, $code), 'expires_at' => now()->addMinutes(10),
        ]);
        try {
            $this->sender->send($email, 'SIAC-SG · Verifica tu nuevo correo', 'Tu código para confirmar el nuevo correo de SIAC-SG es: '.$code."\n\nTu dirección anterior sigue activa hasta confirmar. El código vence en diez minutos y solo se puede usar una vez. Si no solicitaste el cambio, ignora este mensaje.");
        } catch (ValidationException $exception) {
            $challenge->update(['consumed_at' => now()]);
            throw $exception;
        }

        return $id;
    }

    public function confirm(User $actor, string $id, string $code): User
    {
        if (! Str::isUuid($id)) {
            throw ValidationException::withMessages(['code' => 'Solicita un código para verificar el nuevo correo.']);
        }
        $result = DB::transaction(function () use ($actor, $id, $code) {
            $challenge = PatientAccessChallenge::whereKey($id)->lockForUpdate()->first();
            if (! $challenge || $challenge->user_id !== $actor->id || $challenge->purpose !== 'email'
                || $challenge->consumed_at || $challenge->expires_at->isPast() || $challenge->attempts >= 5) {
                return null;
            }
            $challenge->increment('attempts');
            if (! hash_equals($challenge->token_hash, $this->signature($id, $code))) {
                return null;
            }
            $user = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            $data = $challenge->payload;
            if ($user->auth_version !== $challenge->auth_version || $user->email !== $data['previous_email']) {
                return null;
            }
            $user->forceFill(['email' => $data['email'], 'email_verified_at' => now(), 'auth_version' => $user->auth_version + 1, 'remember_token' => Str::random(60)])->save();
            $challenge->update(['consumed_at' => now()]);
            $this->audit->record($user, $user, 'actualizar_correo', null, ['fields' => ['email'], 'verified' => true]);

            return $user;
        });
        if (! $result) {
            throw ValidationException::withMessages(['code' => 'El código no es válido, venció o ya fue utilizado. Solicita otro.']);
        }

        return $result;
    }
}
