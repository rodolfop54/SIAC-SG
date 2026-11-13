import { Link, usePage } from '@inertiajs/react';
import { ArrowRight, CalendarDays, CheckCircle2, Clock3, Copy, Check } from 'lucide-react';
import { useState } from 'react';
import Layout from '../components/Layout';
import { Badge, Steps, Notice } from '../components/UI';
import { dateLabel, timeLabel } from '../types';
import type { Appointment, Shared } from '../types';
export default function Confirmation({ appointment: a }: { appointment: Appointment }) {
  const [copied, setCopied] = useState(false);
  const { auth } = usePage<Shared>().props;
  return (
    <Layout
      title={a.status === 'CONFIRMADA' ? 'Reserva confirmada' : 'Detalle de la cita'}
      description="Tu información está guardada. Puedes consultarla cuando lo necesites."
    >
      <Steps step={2} />
      <section className="confirmation panel">
        <span className="confirmation-icon">
          <CheckCircle2 size={38} />
        </span>
        <h2>
          {a.status === 'CONFIRMADA' ? 'Tu cita de prueba está lista' : 'Esta cita está cancelada'}
        </h2>
        <p className="muted">
          {a.status === 'CONFIRMADA'
            ? 'La reserva se guardó correctamente en tu espacio.'
            : 'Conservamos la información en tu historial.'}
        </p>
        <Badge status={a.status} />
        <div className="confirmation-summary">
          <h3>{a.slot.service.name}</h3>
          <p>
            <CalendarDays size={18} />
            {dateLabel(a.slot.starts_at, {
              weekday: 'long',
              day: 'numeric',
              month: 'long',
              year: 'numeric',
            })}
          </p>
          <p>
            <Clock3 size={18} />
            {timeLabel(a.slot.starts_at)} – {timeLabel(a.slot.ends_at)} · Colombia
          </p>
          <p>Cuenta ficticia: {auth.user?.alias}</p>
        </div>
        <div className="code-block">
          <span>Código de reserva</span>
          <code>{a.code}</code>
          <button
            className="icon-button"
            aria-label="Copiar código"
            onClick={async () => {
              try {
                await navigator.clipboard.writeText(a.code);
                setCopied(true);
              } catch {
                setCopied(false);
              }
            }}
          >
            {copied ? <Check size={17} /> : <Copy size={17} />}
          </button>
        </div>
        {copied && <span role="status">Código copiado.</span>}
        <Notice>
          Esta es una reserva ficticia. No asistas a ninguna institución de salud con este código.
        </Notice>
        <div className="button-row">
          <Link href="/mis-citas" className="button">
            Ir a mis citas
            <ArrowRight size={17} />
          </Link>
          <Link href="/disponibilidad" className="button secondary">
            Consultar otro horario
          </Link>
        </div>
      </section>
    </Layout>
  );
}
