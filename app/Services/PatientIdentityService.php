<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PatientIdentityService
{
    public const TYPES = [
        'CC' => 'Cédula de ciudadanía', 'TI' => 'Tarjeta de identidad',
        'RC' => 'Registro civil', 'CE' => 'Cédula de extranjería',
        'PA' => 'Pasaporte', 'PPT' => 'Permiso por protección temporal',
        'PE' => 'Permiso especial de permanencia', 'SC' => 'Salvoconducto',
        'CD' => 'Carné diplomático', 'DE' => 'Documento extranjero',
        'CN' => 'Certificado de nacido vivo',
    ];

    public function normalize(string $number): string
    {
        return strtoupper(preg_replace('/[\s.\-]+/u', '', trim($number)));
    }

    public function document(string $type, string $number): array
    {
        $number = $this->normalize($number);

        return Validator::make(['document_type' => $type, 'document_number' => $number], [
            'document_type' => ['required', Rule::in(array_keys(self::TYPES))],
            'document_number' => ['required', 'string', 'min:6', 'max:20', 'regex:/^(LAB|FICT)[A-Z0-9]+$/'],
        ], ['document_number.regex' => 'Usa un identificador sintético con prefijo LAB o FICT, por ejemplo LAB000001. No escribas documentos reales.'])->validate();
    }

    public function key(string $type, string $number): string
    {
        return hash_hmac('sha256', $type.':'.$this->normalize($number), config('app.key'));
    }

    public function find(string $type, string $number): ?User
    {
        return User::where('document_key', $this->key($type, $number))->where('role', 'user')->first();
    }

    public function verify(User $user, string $password, string $ip): void
    {
        $key = 'identity:'.$user->id.':'.$ip;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['current_password' => 'Demasiados intentos. Espera un minuto y vuelve a probar.']);
        }
        if (! Hash::check($password, $user->password)) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['current_password' => 'La clave de acceso no coincide. No se mostraron ni modificaron citas.']);
        }
        RateLimiter::clear($key);
    }

    public function create(string $name, string $type, string $number, string $password, bool $web = false): User
    {
        $document = $this->document($type, $number);
        Validator::make(['name' => trim($name), 'password' => $password], ['name' => 'required|string|min:3|max:100', 'password' => 'required|string|min:8|max:72'])->validate();
        try {
            return DB::transaction(function () use ($name, $document, $password) {
                $user = new User(['name' => trim($name), 'alias' => 'PAC-'.strtoupper(Str::random(16)), 'role' => 'user', 'password' => $password]);
                $user->forceFill([...$document, 'document_key' => $this->key($document['document_type'], $document['document_number']), 'web_registered_at' => now(), 'password_set_at' => now()]);
                $user->save();

                return $user;
            });
        } catch (UniqueConstraintViolationException $e) {
            throw ValidationException::withMessages(['document_number' => 'Este documento ya tiene un perfil. Identifícate con su clave para conservar sus citas.']);
        }
    }

    public function register(array $data, ?int $verifiedPatientId, string $ip): User
    {
        $document = $this->document($data['document_type'], $data['document_number']);
        $existing = $this->find($document['document_type'], $document['document_number']);
        if (! $existing) {
            return $this->create($data['name'], $document['document_type'], $document['document_number'], $data['password'], true);
        }
        if ($existing->web_registered_at) {
            throw ValidationException::withMessages(['document_number' => 'No se puede crear otra cuenta con estos datos. Si ya te registraste, inicia sesión con tu documento.']);
        }
        if ($verifiedPatientId !== $existing->id) {
            $this->verify($existing, $data['current_password'] ?? '', $ip);
        }

        return DB::transaction(function () use ($existing, $data) {
            $user = User::lockForUpdate()->findOrFail($existing->id);
            if ($user->web_registered_at) {
                throw ValidationException::withMessages(['document_number' => 'La cuenta ya fue activada. Inicia sesión.']);
            }
            // Keep the original patient id, document, name, appointments and audit references.
            $user->forceFill(['password' => $data['password'], 'web_registered_at' => now(), 'password_set_at' => now(), 'auth_version' => $user->auth_version + 1, 'remember_token' => Str::random(60)])->save();

            return $user;
        });
    }
}
