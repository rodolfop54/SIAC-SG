<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class PatientEmailSender
{
    public function send(string $email, string $subject, string $text): void
    {
        if (! config('patient_access.enabled') || ! config('patient_access.brevo_key') || ! config('patient_access.from_address')) {
            throw ValidationException::withMessages(['email' => 'El envío de correos todavía no está configurado. Puedes usar tu contraseña actual.']);
        }

        try {
            $response = Http::connectTimeout(5)->timeout(12)
                ->withHeaders(['api-key' => config('patient_access.brevo_key')])->acceptJson()
                ->post('https://api.brevo.com/v3/smtp/email', [
                    'sender' => ['email' => config('patient_access.from_address'), 'name' => config('patient_access.from_name')],
                    'to' => [['email' => $email]], 'subject' => $subject, 'textContent' => $text,
                ]);
            if ($response->successful() && $response->json('messageId')) {
                return;
            }
        } catch (ConnectionException) {
            // Return an actionable message without including provider data or the access code.
        }

        throw ValidationException::withMessages(['email' => 'No pudimos entregar el correo. Espera un momento y vuelve a solicitarlo.']);
    }
}
