<?php

namespace App\Http\Controllers;

use App\Services\PatientEmailAccess;
use App\Services\PatientIdentityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PatientEmailAccessController extends Controller
{
    public function show(Request $request)
    {
        return Inertia::render('EmailAccess', [
            'documentTypes' => PatientIdentityService::TYPES, 'signup' => $request->boolean('registro'),
            'challenge' => $request->session()->has('patient_email_challenge'),
            'emailEnabled' => (bool) config('patient_access.enabled'),
        ]);
    }

    public function requestCode(Request $request, PatientEmailAccess $access, PatientIdentityService $identity)
    {
        $data = $request->validate([
            'document_type' => 'required|string|max:4', 'document_number' => 'required|string|max:30',
            'name' => 'nullable|string|min:3|max:100', 'email' => 'nullable|email:rfc|max:254',
            'current_password' => 'nullable|string|max:72',
        ]);
        $user = $identity->find($data['document_type'], $data['document_number']);
        $verifiedId = $request->user()?->role === 'user' ? $request->user()->id : null;
        if (! $verifiedId && $user && now()->timestamp - $request->session()->get('bot_last_activity', 0) < 900
            && $request->session()->get('bot_patient_id') === $user->id
            && $request->session()->get('bot_auth_version', 0) === $user->auth_version) {
            $verifiedId = $request->session()->get('bot_patient_id');
        }
        if ($user && ! $user->email_verified_at && ! empty($data['current_password'])) {
            $identity->verify($user, $data['current_password'], $request->ip());
            $verifiedId = $user->id;
        }
        $id = $access->request($data, $request->ip(), $verifiedId);
        $request->session()->put('patient_email_challenge', $id);

        return redirect('/acceso/codigo')->with('success', 'Revisa el correo asociado a tu cuenta e introduce el código.');
    }

    public function verify(Request $request, PatientEmailAccess $access)
    {
        $data = $request->validate(['code' => 'required|digits:6']);
        $user = $access->consume($request->session()->get('patient_email_challenge', ''), $data['code']);
        $request->session()->forget('patient_email_challenge');
        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put(['last_activity' => now()->timestamp, 'auth_version' => $user->auth_version, 'auth_confirmed_at' => now()->timestamp]);

        return redirect('/mis-citas')->with('success', 'Tu cuenta está lista para el portal y el bot.');
    }

    public function restart(Request $request)
    {
        $request->session()->forget('patient_email_challenge');

        return redirect('/acceso/codigo');
    }

    public function recovery()
    {
        return Inertia::render('RecoverAccess', ['documentTypes' => PatientIdentityService::TYPES, 'emailEnabled' => (bool) config('patient_access.enabled')]);
    }

    public function sendReset(Request $request, PatientEmailAccess $access)
    {
        $data = $request->validate(['document_type' => 'required|string|max:4', 'document_number' => 'required|string|max:30']);
        $access->request($data, $request->ip(), purpose: 'reset');

        return back()->with('success', 'Si el documento tiene un correo verificado, recibirás un enlace para crear o cambiar la contraseña.');
    }

    public function resetForm(Request $request, string $id, string $token)
    {
        return Inertia::render('ResetPassword', compact('id', 'token'))->toResponse($request)
            ->header('Referrer-Policy', 'no-referrer')->header('Cache-Control', 'no-store');
    }

    public function resetPassword(Request $request, PatientEmailAccess $access)
    {
        $data = $request->validate(['id' => 'required|uuid', 'token' => 'required|string|max:100', 'password' => 'required|string|min:8|max:72|confirmed']);
        $access->consume($data['id'], $data['token'], 'reset', $data['password']);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/acceso')->with('success', 'Tu contraseña se actualizó. Puedes entrar con tu documento.');
    }
}
