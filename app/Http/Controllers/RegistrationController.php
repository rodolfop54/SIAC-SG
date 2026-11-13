<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PatientIdentityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class RegistrationController extends Controller
{
    public function show(Request $request)
    {
        $id = $request->session()->get('bot_patient_id');
        $patient = $id && now()->timestamp - $request->session()->get('bot_last_activity', 0) < 900 ? User::find($id) : null;

        return Inertia::render('Register', ['documentTypes' => PatientIdentityService::TYPES, 'patientName' => $patient?->name]);
    }

    public function store(Request $request, PatientIdentityService $identity)
    {
        $data = $request->validate([
            'name' => 'required|string|min:3|max:100', 'document_type' => 'required|string|max:4',
            'document_number' => 'required|string|max:30', 'current_password' => 'nullable|string|max:72',
            'password' => 'required|string|min:8|max:72|confirmed',
        ]);
        $verified = now()->timestamp - $request->session()->get('bot_last_activity', 0) < 900 ? $request->session()->get('bot_patient_id') : null;
        $user = $identity->register($data, $verified, $request->ip());
        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('last_activity', now()->timestamp);

        return redirect('/mis-citas')->with('success', 'Tu cuenta web está lista. Tus reservas del bot ya están vinculadas a tu documento.');
    }
}
