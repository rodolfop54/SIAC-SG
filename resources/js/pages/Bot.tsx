import { Link, router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import {
  MessageCircle,
  Send,
  ShieldCheck,
  ArrowUpRight,
  LockKeyhole,
  ArrowLeft,
  CheckCheck,
} from 'lucide-react';
import PublicLayout from '../components/PublicLayout';
import { Errors } from '../components/UI';
import type { Shared } from '../types';
import { timeLabel } from '../types';
type Message = {
  id: number;
  sender: string;
  content: string;
  options: { value: string; label: string }[] | null;
  created_at: string;
  type: string;
};
type Prompt = {
  text: string;
  options?: { value: string; label: string }[];
  label: string;
  placeholder: string;
  inputType: 'text' | 'password' | 'email';
};
type Patient = { name: string; document: string | null; webRegistered: boolean };
type Current = { message_id: number; text: string; options: { value: string; label: string }[] };
export default function Bot({
  messages,
  patient,
  onboarding,
  current,
}: {
  messages: Message[];
  patient: Patient | null;
  onboarding: Prompt | null;
  current: Current | null;
}) {
  const { auth } = usePage<Shared>().props;
  // Inertia adopts successful submissions as defaults; reset must always clear chat secrets.
  const form = useForm(() => ({ message: '' }));
  const end = useRef<HTMLDivElement>(null);
  const conversation = useRef<HTMLDivElement>(null);
  const options = onboarding?.options ?? current?.options;
  useEffect(() => {
    if (onboarding?.options) {
      if (conversation.current) conversation.current.scrollTop = 0;
    } else end.current?.scrollIntoView({ block: 'nearest', behavior: 'instant' });
  }, [messages.length, onboarding?.label, current?.message_id]);
  function send(value: string) {
    form.transform(() => ({ message: value, prompt_id: current?.message_id ?? null }));
    form.post('/bot', { preserveScroll: true, onFinish: () => form.reset() });
  }
  return (
    <PublicLayout title="Bot público · Reserva por chat" chat>
      <main id="main" className="public-bot-main">
        <div className="public-bot-heading">
          <h1>Reserva por chat</h1>
          <p>Empieza o vuelve a tus citas. Una cuenta para el bot y el portal.</p>
        </div>
        <Errors />
        <div className="bot-grid whatsapp-grid">
          <section className="chat-panel" aria-label="Conversación de prueba">
            <header className="chat-header">
              <Link className="chat-back" href="/" aria-label="Volver al inicio">
                <ArrowLeft size={21} />
              </Link>
              <span className="chat-avatar">
                <MessageCircle size={23} />
              </span>
              <div>
                <h2>SIAC-SG · Citas</h2>
                <span>
                  <i className="status-dot" />
                  En línea · Asistente de prueba
                </span>
              </div>
              <ShieldCheck size={20} />
            </header>
            <div className="chat-notice">
              <LockKeyhole size={12} />
              <span>Estilo WhatsApp · Chat de laboratorio</span>
            </div>
            <div
              ref={conversation}
              className="chat-messages"
              role="log"
              aria-label="Historial de conversación"
              aria-live="polite"
              aria-relevant="additions"
            >
              <div className="chat-date">Hoy</div>
              <div className="chat-privacy">
                <LockKeyhole size={12} />
                Tus datos de acceso no aparecen en esta conversación. Usa datos ficticios.
              </div>
              {onboarding && (
                <div className="bubble incoming">
                  <strong>Asistente SIAC-SG</strong>
                  <p>{onboarding.text}</p>
                  <span className="chat-step-note">Identificación del paciente</span>
                </div>
              )}
              {messages.map((m) => (
                <div
                  className={`bubble ${m.sender === 'user' ? 'outgoing' : 'incoming'} ${m.type === 'reminder' ? 'reminder' : ''}`}
                  key={m.id}
                >
                  {m.sender === 'bot' && (
                    <strong>SIAC-SG{m.type === 'reminder' ? ' · Recordatorio interno' : ''}</strong>
                  )}
                  <p>{m.content}</p>
                  <span className="message-meta">
                    <time dateTime={m.created_at}>{timeLabel(m.created_at)}</time>
                    {m.sender === 'user' && <CheckCheck size={14} aria-label="Recibido" />}
                  </span>
                </div>
              ))}
              {current && messages.at(-1)?.id !== current.message_id && (
                <div className="bubble incoming">
                  <strong>SIAC-SG · Opciones actuales</strong>
                  <p>{current.text}</p>
                </div>
              )}
              {options && (
                <div
                  className={`chat-options ${onboarding?.options ? 'document-options' : ''}`}
                  aria-label="Opciones disponibles"
                >
                  {(onboarding?.options ? options.slice(0, 6) : options).map((o) => (
                    <button
                      key={o.value}
                      className="chat-option"
                      disabled={form.processing}
                      onClick={() => send(o.value)}
                    >
                      {o.label}
                      <ArrowUpRight size={14} />
                    </button>
                  ))}
                  {onboarding?.options && options.length > 6 && (
                    <details className="other-document-options">
                      <summary>Otros tipos de documento</summary>
                      <div>
                        {options.slice(6).map((o) => (
                          <button
                            key={o.value}
                            className="chat-option"
                            disabled={form.processing}
                            onClick={() => send(o.value)}
                          >
                            {o.label}
                            <ArrowUpRight size={14} />
                          </button>
                        ))}
                      </div>
                    </details>
                  )}
                </div>
              )}
              <div ref={end} />
            </div>
            <form
              className="chat-composer"
              onSubmit={(e) => {
                e.preventDefault();
                if (form.data.message.trim()) send(form.data.message);
              }}
            >
              <label htmlFor="message" className="sr-only">
                {onboarding?.label ?? 'Mensaje al asistente'}
              </label>
              <input
                id="message"
                type={onboarding?.inputType ?? 'text'}
                value={form.data.message}
                onChange={(e) => form.setData('message', e.target.value)}
                placeholder={onboarding?.placeholder ?? 'Escribe un mensaje'}
                maxLength={onboarding?.inputType === 'email' ? 254 : 100}
                autoComplete="off"
                disabled={form.processing}
              />
              <button
                className="button icon-only"
                aria-label="Enviar opción"
                disabled={form.processing || !form.data.message.trim()}
              >
                <Send size={19} />
              </button>
            </form>
          </section>
          <aside className="bot-guide">
            <h2>Tu asistente de citas</h2>
            <p>
              Identifícate para crear tu cuenta o volver a ella. Tus reservas aparecen también en el
              portal con el mismo perfil.
            </p>
            <div className="bot-guide-rule">
              <ShieldCheck size={22} />
              <div>
                <h3>Con la misma seguridad</h3>
                <p>
                  Tu documento identifica el perfil. Verifica el acceso con el código enviado a tu
                  correo o con la clave de una cuenta anterior.
                </p>
              </div>
            </div>
            <div className="bot-guide-rule">
              <MessageCircle size={22} />
              <div>
                <h3>Siempre puedes volver</h3>
                <p>
                  Escribe <strong>menú</strong> para empezar de nuevo o <strong>0</strong> para
                  cerrar la conversación.
                </p>
              </div>
            </div>
            <p className="bot-disclaimer">
              No es WhatsApp. No usa Meta, Twilio ni servicios de mensajería. Los recordatorios se
              guardan únicamente dentro de este laboratorio.
            </p>
            {patient && (
              <div className="bot-patient">
                <strong>{patient.name}</strong>
                {patient.document && <span>{patient.document}</span>}
                <small>Perfil de paciente identificado</small>
              </div>
            )}
            {patient && !auth.user && (
              <Link href="/bot/abrir-web" method="post" as="button" className="button full">
                Entrar a mi espacio web
                <ArrowUpRight size={16} />
              </Link>
            )}
            {patient && (
              <Link href="/acceso/codigo" className="text-link">
                Configurar acceso por correo
              </Link>
            )}
            {patient && (
              <button
                className="button secondary"
                disabled={form.processing}
                onClick={() => send('menu')}
              >
                Volver al menú
              </button>
            )}
            {!auth.user && (
              <button
                className="text-link bot-reset"
                disabled={form.processing}
                onClick={() => router.post('/bot/cambiar-identidad')}
              >
                {patient ? 'Salir del perfil de paciente' : 'Empezar de nuevo'}
              </button>
            )}
          </aside>
        </div>
      </main>
    </PublicLayout>
  );
}
