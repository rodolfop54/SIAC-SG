<?php

namespace App\Services;

use App\Models\PatientAccessChallenge;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PatientEmailAccess
{
    public function __construct(private PatientIdentityService $identity, private PatientEmailSender $sender) {}

    private function throttle(string $key): void
    {
        if (RateLimiter::tooManyAttempts($key, 1)) {
            throw ValidationException::withMessages(['email' => 'Espera un minuto antes de solicitar otro correo.']);
        }
        RateLimiter::hit($key, 60);
    }

    private function signature(string $id, string $token): string
    {
        return hash_hmac('sha256', $id.':'.$token, config('app.key'));
    }

    public function request(array $data, string $ip, ?int $verifiedPatientId = null, string $purpose = 'access'): string
    {
        if (! config('patient_access.enabled')) {
            throw ValidationException::withMessages(['email' => 'El acceso por correo todavía no está disponible. Puedes usar tu contraseña actual.']);
        }
        $document = $this->identity->document($data['document_type'], $data['document_number']);
        $key = $this->identity->key($document['document_type'], $document['document_number']);
        $this->throttle('patient-email:'.$purpose.':'.$key);
        if (RateLimiter::tooManyAttempts('patient-email-ip:'.$ip, 10)) {
            throw ValidationException::withMessages(['email' => 'Se alcanzó el límite de solicitudes. Vuelve a intentarlo más tarde.']);
        }
        RateLimiter::hit('patient-email-ip:'.$ip, 3600);

        $user = $this->identity->find($document['document_type'], $document['document_number']);
        $email = $user?->email_verified_at ? $user->email : mb_strtolower(trim($data['email'] ?? ''));
        $hasVerifiedEmail = $user && $user->email_verified_at;
        $canEnroll = $user && $verifiedPatientId === $user->id;
        if ($purpose === 'reset' && ! $hasVerifiedEmail) {
            return (string) Str::uuid();
        }
        if (! $hasVerifiedEmail && $user && ! $canEnroll) {
            throw ValidationException::withMessages(['current_password' => 'Entra con tu clave actual para asociar un correo verificado a este perfil.']);
        }
        if (! $hasVerifiedEmail && (! filter_var($email, FILTER_VALIDATE_EMAIL) || (! $user && empty($data['name'])))) {
            throw ValidationException::withMessages(['email' => 'Para crear tu cuenta, escribe un nombre ficticio y un correo que puedas verificar.']);
        }

        $id = (string) Str::uuid();
        $token = $purpose === 'reset' ? Str::random(48) : (string) random_int(100000, 999999);
        $challenge = PatientAccessChallenge::create([
            'id' => $id, 'document_key' => $key, 'user_id' => $user?->id,
            'auth_version' => $user?->auth_version ?? 0, 'purpose' => $purpose,
            'payload' => [...$document, 'name' => $user?->name ?? trim($data['name']), 'email' => $email],
            'token_hash' => $this->signature($id, $token), 'expires_at' => now()->addMinutes(10),
        ]);
        $text = $purpose === 'reset'
            ? 'Abre este enlace para crear o cambiar tu contraseña de SIAC-SG: '.rtrim(config('app.url'), '/').'/recuperar/'.$id.'/'.$token
            : 'Tu código de acceso a SIAC-SG es: '.$token;
        try {
            $this->sender->send($email, $purpose === 'reset' ? 'SIAC-SG · Crea o recupera tu contraseña' : 'SIAC-SG · Código de acceso', $text."\n\nVence en 10 minutos y solo se puede usar una vez. Si no lo solicitaste, ignora este correo.");
        } catch (ValidationException $exception) {
            $challenge->update(['consumed_at' => now()]);
            throw $exception;
        }

        return $id;
    }

    public function consume(string $id, string $token, string $purpose = 'access', ?string $password = null): User
    {
        if (! Str::isUuid($id)) {
            throw ValidationException::withMessages(['code' => 'Solicita un código o enlace nuevo para continuar.']);
        }
        try {
            $result = DB::transaction(function () use ($id, $token, $purpose, $password) {
                $challenge = PatientAccessChallenge::whereKey($id)->lockForUpdate()->first();
                if (! $challenge || $challenge->purpose !== $purpose || $challenge->consumed_at || $challenge->expires_at->isPast() || $challenge->attempts >= 5) {
                    return null;
                }
                $challenge->increment('attempts');
                if (! hash_equals($challenge->token_hash, $this->signature($id, $token))) {
                    return null;
                }
                $data = $challenge->payload;
                $user = User::where('document_key', $challenge->document_key)->where('role', 'user')->lockForUpdate()->first();
                if ($user && ! $challenge->user_id && ! $user->email_verified_at) {
                    return null;
                }
                if ($user && (($challenge->user_id && $challenge->user_id !== $user->id) || $user->auth_version !== $challenge->auth_version || ($user->email_verified_at && mb_strtolower($user->email) !== mb_strtolower($data['email'])))) {
                    return null;
                }
                if ($purpose === 'reset' && (! $user || ! $user->email_verified_at)) {
                    return null;
                }
                if (! $user) {
                    $user = $this->identity->create($data['name'], $data['document_type'], $data['document_number'], Str::random(48), true);
                    $user->password_set_at = null;
                }
                $user->forceFill(['email' => $data['email'], 'email_verified_at' => now(), 'web_registered_at' => $user->web_registered_at ?? now()]);
                if ($password !== null) {
                    $user->forceFill(['password' => $password, 'password_set_at' => now(), 'auth_version' => $user->auth_version + 1, 'remember_token' => Str::random(60)]);
                }
                $user->save();
                $challenge->update(['consumed_at' => now()]);

                return $user;
            });
        } catch (UniqueConstraintViolationException) {
            $result = null;
        }
        if (! $result) {
            throw ValidationException::withMessages(['code' => 'El código o enlace no es válido, venció o ya fue utilizado. Solicita otro.']);
        }

        return $result;
    }
}
