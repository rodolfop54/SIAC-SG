import { Link, router, useForm } from '@inertiajs/react';
import { useRef, useState } from 'react';
import { ArrowRight, CalendarDays, Clock3, Plus, RefreshCw, X } from 'lucide-react';
import Layout from '../components/Layout';
import { Badge, Empty, Pagination } from '../components/UI';
import { dateLabel, timeLabel } from '../types';
import type { Appointment, Paginated } from '../types';
export default function Appointments({
  appointments,
  status,
}: {
  appointments: Paginated<Appointment>;
  status: string;
}) {
  const [target, setTarget] = useState<Appointment | null>(null);
  const dialog = useRef<HTMLDialogElement>(null);
  const trigger = useRef<HTMLElement | null>(null);
  const form = useForm({ confirmed: true });
  function close() {
    dialog.current?.close();
    setTarget(null);
    trigger.current?.focus();
  }
  return (
    <Layout
      title="Mis citas"
      description="Consulta tus reservas y conserva el control de tus horarios."
      action={
        <Link href="/disponibilidad" className="button">
          <Plus size={17} />
          Nueva reserva
        </Link>
      }
    >
      <div className="tabs" role="navigation" aria-label="Filtrar citas">
        {[
          ['', 'Todas'],
          ['CONFIRMADA', 'Confirmadas'],
          ['CANCELADA', 'Canceladas'],
        ].map(([value, label]) => (
          <Link
            key={value}
            href={'/mis-citas' + (value ? '?status=' + value : '')}
            className={status === value ? 'selected' : ''}
            aria-current={status === value ? 'page' : undefined}
          >
            {label}
          </Link>
        ))}
      </div>
      <p className="result-count">
        {appointments.total}{' '}
        {appointments.total === 1 ? 'cita en tu historial' : 'citas en tu historial'}
      </p>
      {appointments.data.length ? (
        <div className="appointment-list">
          {appointments.data.map((a) => {
            const editable = a.status === 'CONFIRMADA' && new Date(a.slot.starts_at) > new Date();
            return (
              <article key={a.id} className="appointment-card panel">
                <div className="appointment-date">
                  <strong>{dateLabel(a.slot.starts_at, { day: '2-digit' })}</strong>
                  <span>{dateLabel(a.slot.starts_at, { month: 'short' })}</span>
                </div>
                <div className="appointment-info">
                  <div className="appointment-title">
                    <h2>{a.slot.service.name}</h2>
                    <Badge status={a.status} />
                  </div>
                  <div className="appointment-meta">
                    <span>
                      <CalendarDays size={15} />
                      {dateLabel(a.slot.starts_at, {
                        weekday: 'long',
                        day: 'numeric',
                        month: 'long',
                        year: 'numeric',
                      })}
                    </span>
                    <span>
                      <Clock3 size={15} />
                      {timeLabel(a.slot.starts_at)} – {timeLabel(a.slot.ends_at)}
                    </span>
                  </div>
                  <p className="appointment-code">
                    Código: <code>{a.code}</code>
                  </p>
                </div>
                <div className="appointment-actions">
                  <Link className="text-link" href={`/citas/${a.id}`}>
                    Ver detalle
                    <ArrowRight size={14} />
                  </Link>
                  {editable && (
                    <>
                      <Link
                        href={`/disponibilidad?appointment=${a.id}`}
                        className="button secondary"
                      >
                        <RefreshCw size={15} />
                        Reprogramar
                      </Link>
                      <button
                        className="button danger-quiet"
                        onClick={(e) => {
                          trigger.current = e.currentTarget;
                          setTarget(a);
                          dialog.current?.showModal();
                        }}
                      >
                        Cancelar cita
                      </button>
                    </>
                  )}
                </div>
              </article>
            );
          })}
        </div>
      ) : (
        <div className="panel">
          <Empty
            title="No hay citas para mostrar"
            action={
              <Link href="/disponibilidad" className="button">
                Consultar disponibilidad
                <ArrowRight size={16} />
              </Link>
            }
          >
            Elige un horario y confirma tu primera reserva de prueba.
          </Empty>
        </div>
      )}
      <Pagination data={appointments} />
      <dialog
        ref={dialog}
        className="cancel-dialog"
        onCancel={(e) => {
          e.preventDefault();
          if (!form.processing) close();
        }}
        aria-labelledby="cancel-title"
      >
        <div className="dialog-heading">
          <h2 id="cancel-title">¿Cancelar esta cita?</h2>
          <button
            className="icon-button"
            onClick={close}
            disabled={form.processing}
            aria-label="Cerrar confirmación"
          >
            <X size={20} />
          </button>
        </div>
        {target && (
          <>
            <p>
              Se liberará el horario de <strong>{target.slot.service.name}</strong> del{' '}
              {dateLabel(target.slot.starts_at)} a las {timeLabel(target.slot.starts_at)}.
            </p>
            <p className="muted">
              La cita seguirá visible en tu historial. Si cambias de opinión, podrás crear una nueva
              reserva.
            </p>
            <div className="button-row">
              <button
                autoFocus
                className="button secondary"
                disabled={form.processing}
                onClick={close}
              >
                Conservar cita
              </button>
              <button
                className="button danger"
                disabled={form.processing}
                onClick={() => form.post(`/citas/${target.id}/cancelar`, { onSuccess: close })}
              >
                {form.processing ? 'Cancelando…' : 'Sí, cancelar cita'}
              </button>
            </div>
          </>
        )}
      </dialog>
    </Layout>
  );
}
