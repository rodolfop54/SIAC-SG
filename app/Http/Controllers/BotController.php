<?php

namespace App\Http\Controllers;

use App\Models\SimulatedMessage;
use App\Models\User;
use App\Services\BotService;
use App\Services\PatientEmailAccess;
use App\Services\PatientIdentityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class BotController extends Controller
{
    private function actor(Request $r): ?User
    {
        if ($r->session()->has('bot_last_activity') && now()->timestamp - $r->session()->get('bot_last_activity') >= 900) {
            $this->clear($r);
        }
        if ($id = $r->session()->get('bot_patient_id')) {
            $patient = User::where('role', 'user')->find($id);
            if ($patient && $r->session()->get('bot_auth_version', 0) === $patient->auth_version) {
                return $patient;
            }
            $this->clear($r);

            return null;
        }

        return ! $r->session()->get('bot_closed') && $r->user()?->role === 'user' ? $r->user() : null;
    }

    private function clear(Request $r): void
    {
        $r->session()->forget(['bot_patient_id', 'bot_identity', 'bot_last_activity', 'bot_actor_id', 'bot', 'bot_auth_version', 'bot_confirmed_at']);
        $r->session()->put('bot_closed', true);
        Inertia::clearHistory();
    }

    private function conversation(Request $r, User $actor, BotService $bot): array
    {
        if ($r->session()->get('bot_actor_id') !== $actor->id || ! $r->session()->has('bot.prompt')) {
            $r->session()->put(['bot_actor_id' => $actor->id, 'bot' => $bot->begin($actor)]);
        }

        return $r->session()->get('bot');
    }

    private function prompt(string $step): array
    {
        return match ($step) {
            'document_type' => ['text' => 'Hola, soy tu asistente SIAC-SG. Para empezar o volver a tus citas, elige tu tipo de documento. Tu cuenta funciona en el bot y en el portal. Usa datos ficticios en este laboratorio.', 'options' => collect(PatientIdentityService::TYPES)->map(fn ($label, $value) => ['value' => $value, 'label' => $label.' ('.$value.')'])->values()->all(), 'label' => 'Tipo de documento', 'placeholder' => 'Elige una opción de arriba', 'inputType' => 'text'],
            'document_number' => ['text' => 'Escribe un identificador de laboratorio con prefijo LAB o FICT, por ejemplo LAB000001. No uses un documento real. No aparecerá en la conversación.', 'label' => 'Número de documento ficticio', 'placeholder' => 'Ejemplo: LAB000001', 'inputType' => 'text'],
            'name' => ['text' => '¿Cómo te llamas? Escribe tus nombres y apellidos de prueba para crear tu perfil de paciente.', 'label' => 'Nombre completo ficticio', 'placeholder' => 'Nombres y apellidos de prueba', 'inputType' => 'text'],
            'new_secret' => ['text' => 'Crea una clave de acceso de 8 a 72 caracteres. La misma clave permite entrar al bot y al portal con tus citas. No aparecerá en el chat.', 'label' => 'Crear clave de acceso', 'placeholder' => 'Crea una clave de al menos 8 caracteres', 'inputType' => 'password'],
            'confirm_secret' => ['text' => 'Repite la clave de acceso para confirmar tu perfil. Guárdala: el documento por sí solo no permite consultar tus citas.', 'label' => 'Confirmar clave de acceso', 'placeholder' => 'Repite tu clave de acceso', 'inputType' => 'password'],
            'existing_secret' => ['text' => 'Escribe tu contraseña para continuar. La misma cuenta funciona en el bot y el portal. El documento por sí solo no permite ver ni modificar citas.', 'label' => 'Clave de acceso del paciente', 'placeholder' => 'Tu clave de acceso', 'inputType' => 'password'],
            'email' => ['text' => 'Escribe un correo que puedas verificar. Recibirás un código para crear tu cuenta compartida por el bot y el portal.', 'label' => 'Correo de acceso', 'placeholder' => 'tu-correo@ejemplo.com', 'inputType' => 'email'],
            'email_code' => ['text' => 'Introduce el código de seis dígitos enviado al correo asociado a tu solicitud. Vence en diez minutos. Para solicitar otro, vuelve a empezar.', 'label' => 'Código de acceso', 'placeholder' => 'Seis dígitos', 'inputType' => 'text'],
            'ended' => ['text' => 'La conversación finalizó. No se confirmó ninguna operación pendiente. Puedes iniciar otra conversación cuando quieras.', 'options' => [['value' => 'identificar', 'label' => 'Iniciar conversación']], 'label' => 'Mensaje al asistente', 'placeholder' => 'Escribe menú para empezar', 'inputType' => 'text'],
            default => ['text' => 'Elige una opción o escribe menú para volver.', 'label' => 'Mensaje al asistente', 'placeholder' => 'Escribe una opción o menú', 'inputType' => 'text'],
        };
    }

    public function index(Request $r, BotService $bot)
    {
        $actor = $this->actor($r);
        if ($actor) {
            $r->session()->put('bot_last_activity', now()->timestamp);
            $state = $this->conversation($r, $actor, $bot);
        }
        $step = $actor ? 'menu' : $r->session()->get('bot_identity.step', 'document_type');

        return Inertia::render('Bot', [
            'messages' => $actor ? SimulatedMessage::where('user_id', $actor->id)->latest('id')->limit(100)->get()->reverse()->values() : [],
            'patient' => $actor ? ['name' => $actor->name, 'document' => $actor->maskedDocument(), 'webRegistered' => (bool) $actor->web_registered_at || ! $actor->document_key] : null,
            'onboarding' => $actor ? null : $this->prompt($step),
            'current' => $actor ? $state['prompt'] : null,
        ]);
    }

    public function send(Request $r, BotService $bot, PatientIdentityService $identity, PatientEmailAccess $access)
    {
        $maximum = $r->session()->get('bot_identity.step') === 'email' ? 254 : 100;
        $d = $r->validate(['message' => 'required|string|max:'.$maximum, 'prompt_id' => 'nullable|integer']);
        $actor = $this->actor($r);
        $command = mb_strtolower(trim($d['message']));
        if (in_array($command, ['0', 'salir'])) {
            $this->clear($r);
            $r->session()->put('bot_identity', ['step' => 'ended']);

            return redirect('/bot');
        }
        if (! $actor && in_array($command, ['menu', 'menú', 'identificar'])) {
            $this->clear($r);

            return redirect('/bot');
        }
        if ($actor) {
            $r->session()->put('bot_last_activity', now()->timestamp);
            $state = $this->conversation($r, $actor, $bot);
            if (isset($d['prompt_id']) && (int) $d['prompt_id'] !== $state['prompt']['message_id']) {
                return redirect('/bot')->withErrors(['message' => 'Las opciones cambiaron. Revisa las opciones actuales antes de continuar.']);
            }
            $state = $bot->respond($actor, $d['message'], $state);
            $r->session()->put('bot', $state);

            return redirect('/bot');
        }
        $state = $r->session()->get('bot_identity', ['step' => 'document_type']);
        $input = in_array($state['step'], ['new_secret', 'confirm_secret', 'existing_secret']) ? $d['message'] : trim($d['message']);
        switch ($state['step']) {
            case 'document_type':
                if (! isset(PatientIdentityService::TYPES[strtoupper($input)])) {
                    throw ValidationException::withMessages(['message' => 'Elige uno de los tipos de documento disponibles.']);
                }
                $state = ['step' => 'document_number', 'document_type' => strtoupper($input)];
                break;
            case 'document_number':
                $document = $identity->document($state['document_type'], $input);
                $state['document_number'] = $document['document_number'];
                $existing = $identity->find($state['document_type'], $state['document_number']);
                if (config('patient_access.enabled') && $existing?->email_verified_at) {
                    $state['challenge'] = $access->request($state, $r->ip());
                    $state['step'] = 'email_code';
                } else {
                    $state['step'] = $existing ? 'existing_secret' : 'name';
                }
                break;
            case 'name':
                Validator::make(['name' => $input], ['name' => 'required|string|min:3|max:100|regex:/[\pL]/u'])->validate();
                $state['name'] = $input;
                $state['step'] = config('patient_access.enabled') ? 'email' : 'new_secret';
                break;
            case 'email':
                Validator::make(['email' => $input], ['email' => 'required|email:rfc|max:254'])->validate();
                $state['email'] = $input;
                $state['challenge'] = $access->request($state, $r->ip());
                $state['step'] = 'email_code';
                break;
            case 'email_code':
                $actor = $access->consume($state['challenge'], $input);
                break;
            case 'new_secret':
                Validator::make(['password' => $input], ['password' => 'required|string|min:8|max:72'])->validate();
                $state['secret_hash'] = Hash::make($input);
                $state['step'] = 'confirm_secret';
                break;
            case 'confirm_secret':
                if (! Hash::check($input, $state['secret_hash'])) {
                    throw ValidationException::withMessages(['message' => 'Las claves no coinciden. Repite la clave que acabas de crear.']);
                }
                $actor = $identity->create($state['name'], $state['document_type'], $state['document_number'], $input);
                break;
            case 'existing_secret':
                $actor = $identity->find($state['document_type'], $state['document_number']);
                if (! $actor) {
                    throw ValidationException::withMessages(['message' => 'Vuelve a identificarte para continuar.']);
                }
                $identity->verify($actor, $input, $r->ip());
                break;
        }
        if ($actor) {
            $r->session()->regenerate();
            $r->session()->forget('bot_identity');
            $r->session()->put(['bot_patient_id' => $actor->id, 'bot_last_activity' => now()->timestamp, 'bot_confirmed_at' => now()->timestamp, 'bot_actor_id' => $actor->id, 'bot_closed' => false, 'bot_auth_version' => $actor->auth_version, 'bot' => $bot->begin($actor)]);
        } else {
            $r->session()->put('bot_identity', $state);
        }

        return redirect('/bot');
    }

    public function reset(Request $r)
    {
        $this->clear($r);

        return redirect('/bot');
    }

    public function openWeb(Request $request)
    {
        $patient = $this->actor($request);
        abort_unless($patient, 403);
        $confirmed = max($request->session()->get('bot_confirmed_at', 0), $request->user()?->id === $patient->id ? $request->session()->get('auth_confirmed_at', 0) : 0);
        Auth::login($patient);
        $request->session()->regenerate();
        $request->session()->put(['last_activity' => now()->timestamp, 'auth_version' => $patient->auth_version, 'auth_confirmed_at' => $confirmed]);

        return redirect('/mis-citas');
    }
}
