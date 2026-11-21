<?php

namespace App\Http\Controllers;

use App\Models\PatientAccessChallenge;
use App\Models\User;
use App\Services\AuditService;
use App\Services\PatientEmailAccess;
use App\Services\PatientIdentityService;
use App\Services\ProfileEmailChange;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ProfileController extends Controller
{
    private function freshAccess(Request $request): bool
    {
        $confirmed = $request->session()->get('auth_confirmed_at', 0);

        return $confirmed > 0 && now()->timestamp - $confirmed < 300;
    }

    private function canConfirmByEmail(User $user): bool
    {
        return $user->role === 'user' && $user->document_key && $user->email_verified_at;
    }

    public function show(Request $request)
    {
        $user = $request->user();
        $pending = PatientAccessChallenge::whereKey($request->session()->get('profile_email_challenge'))
            ->where('user_id', $user->id)->where('purpose', 'email')->whereNull('consumed_at')->first();

        return Inertia::render('Profile', [
            'profile' => ['name' => $user->name, 'email' => $user->email, 'emailVerified' => (bool) $user->email_verified_at,
                'document' => $user->maskedDocument(), 'hasPassword' => ! ($user->document_key && $user->email_verified_at && ! $user->password_set_at)],
            'emailEnabled' => (bool) config('patient_access.enabled'), 'freshAccess' => $this->freshAccess($request),
            'canConfirmByEmail' => $this->canConfirmByEmail($user),
            'accessChallenge' => $request->session()->has('profile_access_challenge'),
            'pendingEmail' => $pending?->payload['email'],
        ]);
    }

    public function updateName(Request $request, AuditService $audit)
    {
        $data = $request->validate(['name' => 'required|string|min:3|max:100|regex:/[\pL]/u']);
        $updated = DB::transaction(function () use ($request, $data, $audit) {
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $user->name = trim($data['name']);
            $user->save();
            $audit->record($user, $user, 'actualizar_perfil', null, ['fields' => ['name']]);

            return $user;
        });
        Auth::setUser($updated);

        return back()->with('success', 'Tu nombre se actualizó. Tus citas se conservan.');
    }

    public function requestEmail(Request $request, PatientIdentityService $identity, ProfileEmailChange $change)
    {
        $data = $request->validate(['email' => 'required|email:rfc|max:254', 'current_password' => 'nullable|string|max:72']);
        if (! $this->freshAccess($request)) {
            if (empty($data['current_password'])) {
                throw ValidationException::withMessages(['current_password' => 'Confirma tu acceso con la contraseña actual o un código enviado a tu correo antes de cambiar la dirección.']);
            }
            $identity->verify($request->user(), $data['current_password'], $request->ip());
            $request->session()->put('auth_confirmed_at', now()->timestamp);
        }
        $id = $change->request($request->user(), $data['email'], $request->ip());
        $request->session()->put('profile_email_challenge', $id);

        return back()->with('success', 'Enviamos un código al nuevo correo. La dirección actual se conserva hasta verificarlo.');
    }

    public function confirmEmail(Request $request, ProfileEmailChange $change)
    {
        $data = $request->validate(['code' => 'required|digits:6']);
        $user = $change->confirm($request->user(), $request->session()->get('profile_email_challenge', ''), $data['code']);
        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget(['profile_email_challenge', 'profile_access_challenge']);
        $request->session()->put(['auth_version' => $user->auth_version, 'last_activity' => now()->timestamp, 'auth_confirmed_at' => now()->timestamp]);

        return redirect('/perfil')->with('success', 'Tu nuevo correo está verificado. Los próximos códigos llegarán a esa dirección. Tus citas se conservan.');
    }

    public function cancelEmail(Request $request)
    {
        PatientAccessChallenge::whereKey($request->session()->get('profile_email_challenge'))->where('user_id', $request->user()->id)
            ->where('purpose', 'email')->whereNull('consumed_at')->update(['consumed_at' => now()]);
        $request->session()->forget('profile_email_challenge');

        return redirect('/perfil')->with('success', 'Se canceló el cambio. Tu correo actual sigue activo.');
    }

    public function requestAccess(Request $request, PatientEmailAccess $access)
    {
        $user = $request->user();
        abort_unless($this->canConfirmByEmail($user), 403);
        $id = $access->request(['document_type' => $user->document_type, 'document_number' => $user->document_number], $request->ip(), $user->id);
        $request->session()->put('profile_access_challenge', $id);

        return back()->with('success', 'Enviamos un código a tu correo actual para confirmar tu acceso.');
    }

    public function confirmAccess(Request $request, PatientEmailAccess $access)
    {
        $data = $request->validate(['access_code' => 'required|digits:6']);
        $id = $request->session()->get('profile_access_challenge', '');
        if (! Str::isUuid($id) || ! PatientAccessChallenge::whereKey($id)->where('user_id', $request->user()->id)->where('purpose', 'access')->exists()) {
            throw ValidationException::withMessages(['access_code' => 'Solicita un código nuevo para confirmar tu acceso.']);
        }
        try {
            $user = $access->consume($id, $data['access_code']);
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages(['access_code' => $exception->errors()['code'][0] ?? 'El código no es válido. Solicita otro.']);
        }
        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget('profile_access_challenge');
        $request->session()->put(['auth_version' => $user->auth_version, 'last_activity' => now()->timestamp, 'auth_confirmed_at' => now()->timestamp]);

        return redirect('/perfil')->with('success', 'Acceso confirmado. Ya puedes solicitar el cambio de correo.');
    }
}
