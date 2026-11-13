<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\SimulatedMessage;
use App\Models\Slot;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BotService
{
    public function __construct(private AppointmentService $appointments, private ReminderService $reminders, private AvailabilityService $availability) {}

    public function menu(): array
    {
        return [['value' => '1', 'label' => '1 · Consultar y reservar'], ['value' => '2', 'label' => '2 · Mis citas'], ['value' => '3', 'label' => '3 · Reprogramar'], ['value' => '4', 'label' => '4 · Cancelar'], ['value' => '5', 'label' => '5 · Recordatorios internos'], ['value' => '0', 'label' => '0 · Salir']];
    }

    public function message(User $u, string $text, array $options = [], string $sender = 'bot'): SimulatedMessage
    {
        return SimulatedMessage::create(['user_id' => $u->id, 'sender' => $sender, 'type' => 'conversation', 'content' => $text, 'options' => $options]);
    }

    public function begin(User $u): array
    {
        $text = 'Hola, '.$u->name.'. Tu perfil está identificado. ¿Qué quieres hacer? Solo usamos datos ficticios.';
        $message = $this->message($u, $text, $this->menu());

        return ['step' => 'menu', 'prompt' => ['message_id' => $message->id, 'text' => $text, 'options' => $this->menu()]];
    }

    public function respond(User $u, string $input, array $state): array
    {
        $text = 'Selecciona una opción válida o escribe menú para volver.';
        $options = $this->menu();
        $input = trim($input);
        // Never retain arbitrary free text: it could contain personal information.
        $this->message($u, mb_strlen($input) > 80 ? 'Entrada no válida' : (preg_match('/^(men[uú]|salir|[0-5]|service:\d+|date:\d{4}-\d{2}-\d{2}|slot:\d+|appointment:\d+|confirmar|identificar)$/iu', $input) ? $input : 'Opción no válida'), [], 'user');
        try {
            if ($input === '0' || mb_strtolower($input) === 'salir') {
                $state = ['step' => 'ended'];
                $text = 'La conversación terminó. No se creó ninguna reserva pendiente. Puedes identificarte de nuevo.';
                $options = [['value' => 'identificar', 'label' => 'Iniciar conversación']];
            } elseif (in_array(mb_strtolower($input), ['menú', 'menu', 'identificar'])) {
                $state = ['step' => 'menu'];
                $text = 'Hola, '.$u->name.'. ¿Qué quieres hacer hoy? Solo usamos datos ficticios.';
            } elseif (($state['step'] ?? 'menu') === 'ended') {
                $text = 'Inicia de nuevo para continuar con tu cuenta ficticia.';
                $options = [['value' => 'identificar', 'label' => 'Identificarme como '.$u->alias]];
            } elseif (in_array($input, ['1', '2', '3', '4', '5'])) {
                $state = ['step' => 'menu'];
                if ($input === '1') {
                    $state = ['step' => 'service', 'mode' => 'book'];
                    $text = '¿Qué servicio de prueba necesitas?';
                    $options = Service::where('enabled', true)->get()->map(fn ($s) => ['value' => 'service:'.$s->id, 'label' => $s->name])->all();
                } elseif ($input === '5') {
                    $count = $this->reminders->emit($u, now());
                    $text = $count ? 'Tus recordatorios internos aparecen en esta conversación.' : 'No hay nuevos recordatorios. Solo se generan una vez para citas de las próximas 24 horas.';
                } else {
                    $all = Appointment::with('slot.service')->where('user_id', $u->id)->orderByDesc('id')->get();
                    $text = $all->isEmpty() ? 'Aún no tienes citas. Consulta un horario para comenzar.' : $all->map(fn ($a) => $a->slot->service->name.' · '.$a->slot->starts_at->format('d/m/Y H:i').' · '.$a->status.' · '.$a->code)->join("\n\n");
                    if (in_array($input, ['3', '4'])) {
                        $state = ['step' => 'appointment', 'mode' => $input === '3' ? 'move' : 'cancel'];
                        $text = 'Elige la cita confirmada que quieres '.($input === '3' ? 'reprogramar.' : 'cancelar.');
                        $options = $all->filter(fn ($a) => $a->status === 'CONFIRMADA' && $a->slot->starts_at->isFuture())->map(fn ($a) => ['value' => 'appointment:'.$a->id, 'label' => $a->slot->service->name.' · '.$a->slot->starts_at->format('d/m H:i')])->values()->all();
                        if (! $options) {
                            $text = 'No tienes citas futuras que admitan esta operación.';
                            $options = $this->menu();
                            $state = ['step' => 'menu'];
                        }
                    }
                }
            } elseif (($state['step'] ?? '') === 'appointment' && preg_match('/^appointment:(\d+)$/', $input, $m)) {
                $a = Appointment::with('slot.service')->where('user_id', $u->id)->find($m[1]);
                if (! $a) {
                    throw ValidationException::withMessages(['option' => 'Elige una de tus citas disponibles o vuelve al menú.']);
                }
                Gate::forUser($u)->authorize('update', $a);
                if ($a->status !== 'CONFIRMADA' || $a->slot->starts_at->isPast()) {
                    throw ValidationException::withMessages(['conflict' => 'Esta cita ya no admite cambios.']);
                }
                $state['appointment_id'] = $a->id;
                $state['service_id'] = $a->slot->service_id;
                if ($state['mode'] === 'cancel') {
                    $state['step'] = 'confirm';
                    $text = '¿Confirmas cancelar '.$a->slot->service->name.' del '.$a->slot->starts_at->format('d/m/Y H:i').'? El historial se conserva.';
                    $options = [['value' => 'confirmar', 'label' => 'Sí, cancelar esta cita']];
                } else {
                    $state['step'] = 'date';
                    [$text,$options] = $this->dates($state['service_id']);
                }
            } elseif (($state['step'] ?? '') === 'service' && preg_match('/^service:(\d+)$/', $input, $m)) {
                $s = Service::where('enabled', true)->find($m[1]);
                if (! $s) {
                    throw ValidationException::withMessages(['option' => 'Este servicio no está disponible. Vuelve al menú para consultar los servicios actuales.']);
                }
                $state['service_id'] = $s->id;
                $state['step'] = 'date';
                [$text,$options] = $this->dates($s->id);
            } elseif (($state['step'] ?? '') === 'date' && preg_match('/^date:(\d{4}-\d{2}-\d{2})$/', $input, $m)) {
                Validator::make(['date' => $m[1]], ['date' => 'date_format:Y-m-d'])->validate();
                $state['date'] = $m[1];
                $state['step'] = 'slot';
                $text = 'Elige un horario. Seleccionarlo todavía no reserva.';
                $options = $this->availability->query($state['service_id'])->whereDate('starts_at', $m[1])->get()->map(fn ($s) => ['value' => 'slot:'.$s->id, 'label' => $s->starts_at->format('H:i').' – '.$s->ends_at->format('H:i')])->all();
                if (! $options) {
                    $state['step'] = 'date';
                    [$text,$options] = $this->dates($state['service_id']);
                    $text = 'No quedan horarios para ese día. '.$text;
                }
            } elseif (($state['step'] ?? '') === 'slot' && preg_match('/^slot:(\d+)$/', $input, $m)) {
                $slot = Slot::available()->with('service')->where('service_id', $state['service_id'])->whereDate('starts_at', $state['date'])->find($m[1]);
                if (! $slot) {
                    throw ValidationException::withMessages(['conflict' => 'El horario ya no está disponible. Vuelve al menú y elige otro.']);
                }
                $state['slot_id'] = $slot->id;
                $state['key'] = (string) Str::uuid();
                $state['step'] = 'confirm';
                $text = 'Revisa: '.$slot->service->name.' · '.$slot->starts_at->format('d/m/Y H:i').'. Paciente '.$u->name.'. Es una cita ficticia. ¿Confirmas?';
                $options = [['value' => 'confirmar', 'label' => $state['mode'] === 'move' ? 'Confirmar cambio' : 'Confirmar reserva']];
            } elseif ($input === 'confirmar' && ($state['step'] ?? '') === 'done') {
                $a = Appointment::with('slot.service')->findOrFail($state['result_id']);
                Gate::forUser($u)->authorize('view', $a);
                $text = 'Esta solicitud ya fue procesada. Estado actual: '.$a->status.'. Código: '.$a->code;
            } elseif ($input === 'confirmar' && ($state['step'] ?? '') === 'confirm') {
                if (($state['mode'] ?? '') === 'book') {
                    $a = $this->appointments->book($u, $state['slot_id'], $state['key'], 'bot');
                } else {
                    $a = Appointment::findOrFail($state['appointment_id']);
                    $a = $state['mode'] === 'move' ? $this->appointments->reschedule($u, $a, $state['slot_id'], 'bot') : $this->appointments->cancel($u, $a, 'bot');
                }
                $state['step'] = 'done';
                $state['result_id'] = $a->id;
                $a->load('slot.service');
                $text = 'Operación confirmada. '.$a->slot->service->name.' · '.$a->slot->starts_at->format('d/m/Y H:i').' · '.$a->status."\nCódigo: ".$a->code;
            } else {
                $state = ['step' => 'menu'];
                $options = [['value' => 'menu', 'label' => 'Volver al menú'], ['value' => '0', 'label' => 'Salir']];
            }
        } catch (ValidationException $e) {
            $text = collect($e->errors())->flatten()->join(' ');
            $options = [['value' => 'menu', 'label' => 'Elegir otro horario'], ['value' => '0', 'label' => 'Salir']];
            $state = ['step' => 'menu'];
        }
        if (! in_array($state['step'] ?? '', ['menu', 'ended', 'done'])) {
            $options[] = ['value' => 'menu', 'label' => 'Volver al menú'];
        }
        $message = $this->message($u, $text, $options);
        $state['prompt'] = ['message_id' => $message->id, 'text' => $text, 'options' => $options];

        return $state;
    }

    private function dates(int $service): array
    {
        $dates = $this->availability->query($service)->get()->unique(fn ($s) => $s->starts_at->toDateString())->take(14);

        return ['Selecciona una fecha disponible.', $dates->map(fn ($s) => ['value' => 'date:'.$s->starts_at->toDateString(), 'label' => $s->starts_at->translatedFormat('l d \d\e F')])->values()->all() ?: [['value' => 'menu', 'label' => 'Sin horarios. Volver al menú']]];
    }
}
