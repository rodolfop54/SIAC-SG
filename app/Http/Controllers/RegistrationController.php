<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PatientIdentityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class RegistrationController extends Controller
{
    private function verifiedPatient(Request $request): ?User
    {
        if (now()->timestamp - $request->session()->get('bot_last_activity', 0) >= 900) {
            return null;
        }
        $patient = User::where('role', 'user')->find($request->session()->get('bot_patient_id'));

        return $patient && $request->session()->get('bot_auth_version', 0) === $patient->auth_version ? $patient : null;
    }

    public function show(Request $request)
    {
        if (config('patient_access.enabled')) {
            return redirect('/acceso/codigo?registro=1');
        }
        $patient = $this->verifiedPatient($request);

        return Inertia::render('Register', ['documentTypes' => PatientIdentityService::TYPES, 'patientName' => $patient?->name]);
    }

    public function store(Request $request, PatientIdentityService $identity)
    {
        $data = $request->validate([
            'name' => 'required|string|min:3|max:100', 'document_type' => 'required|string|max:4',
            'document_number' => 'required|string|max:30', 'current_password' => 'nullable|string|max:72',
            'password' => 'required|string|min:8|max:72|confirmed',
        ]);
        $verified = $this->verifiedPatient($request)?->id;
        $user = $identity->register($data, $verified, $request->ip());
        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put(['last_activity' => now()->timestamp, 'auth_version' => $user->auth_version, 'auth_confirmed_at' => now()->timestamp]);

        return redirect('/mis-citas')->with('success', 'Tu cuenta web está lista. Tus reservas del bot ya están vinculadas a tu documento.');
    }
}
