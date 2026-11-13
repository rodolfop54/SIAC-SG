<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PatientIdentityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function show(Request $request)
    {
        return Inertia::render('Login', ['ended' => $request->boolean('ended'), 'documentTypes' => PatientIdentityService::TYPES]);
    }

    public function login(Request $request, PatientIdentityService $identity)
    {
        $data = $request->validate(['alias' => 'nullable|string|max:40', 'document_type' => 'required_without:alias|nullable|string|max:4', 'document_number' => 'required_without:alias|nullable|string|max:30', 'password' => 'required|string|max:200']);
        $alias = ! empty($data['alias']);
        if (! $alias) {
            $identity->document($data['document_type'], $data['document_number']);
        }
        $loginId = $alias ? strtoupper(trim($data['alias'])) : $identity->key($data['document_type'], $data['document_number']);
        $key = 'login:'.hash('sha256', $loginId.'|'.$request->ip());
        $ipKey = 'login-ip:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 6) || RateLimiter::tooManyAttempts($ipKey, 30)) {
            throw ValidationException::withMessages([$alias ? 'alias' : 'document_number' => 'Demasiados intentos fallidos. Espera un minuto antes de volver a intentar.']);
        }
        $user = $alias ? User::where('alias', $loginId)->whereNull('document_key')->first() : $identity->find($data['document_type'], $data['document_number']);
        $valid = $user && ($alias || $user->web_registered_at) && Hash::check($data['password'], $user->password);
        if (! $valid) {
            RateLimiter::hit($key, 60);
            RateLimiter::hit($ipKey, 60);
            throw ValidationException::withMessages([$alias ? 'alias' : 'document_number' => 'Los datos de acceso no coinciden. Si reservaste por el bot y aún no tienes cuenta web, regístrate con el mismo documento.']);
        }
        Auth::login($user);
        RateLimiter::clear($key);
        $request->session()->regenerate();
        $request->session()->put('last_activity', now()->timestamp);

        return redirect($request->user()->role === 'admin' ? '/admin/agenda' : '/disponibilidad');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/acceso?ended=1');
    }
}
