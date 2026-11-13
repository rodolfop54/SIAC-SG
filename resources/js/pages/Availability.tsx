import { Link, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import {
  ArrowLeft,
  ArrowRight,
  CalendarDays,
  Check,
  ChevronLeft,
  ChevronRight,
  Clock3,
  ShieldCheck,
} from 'lucide-react';
import Layout from '../components/Layout';
import { Empty, Notice, Steps } from '../components/UI';
import { dateLabel, timeLabel } from '../types';
import type { Appointment, Service, Shared, Slot } from '../types';
type Props = {
  services: Service[];
  filters: { service_id: number; date: string; month: string };
  slots: Slot[];
  availableDates: string[];
  appointment: Appointment | null;
  requestKey: string;
};
export default function Availability({
  services,
  filters,
  slots,
  availableDates,
  appointment,
  requestKey,
}: Props) {
  const { auth, today } = usePage<Shared>().props;
  const [selected, setSelected] = useState<Slot | null>(null);
  const [review, setReview] = useState(false);
  const form = useForm({ slot_id: 0, request_key: requestKey, confirmed: true });
  const service = services.find((s) => s.id === Number(filters.service_id));
  const [year, month] = filters.month.split('-').map(Number);
  const first = new Date(Date.UTC(year, month - 1, 1));
  const offset = (first.getUTCDay() + 6) % 7;
  const total = new Date(Date.UTC(year, month, 0)).getUTCDate();
  function filter(values: Partial<Props['filters']>) {
    router.get(
      '/disponibilidad',
      { ...filters, ...values, ...(appointment ? { appointment: appointment.id } : {}) },
      { preserveScroll: true },
    );
  }
  function moveMonth(n: number) {
    const d = new Date(Date.UTC(year, month - 1 + n, 1));
    const key = d.toISOString().slice(0, 7);
    filter({ month: key, date: key + '-01' });
  }
  function submit() {
    form.transform((d) => ({ ...d, slot_id: selected!.id }));
    if (appointment) form.patch(`/citas/${appointment.id}`);
    else form.post('/citas');
  }
  return (
    <Layout
      title={appointment ? 'Reprogramar cita' : 'Consultar disponibilidad'}
      description={
        appointment
          ? 'Elige otro horario del mismo servicio. Tu reserva actual se conserva hasta confirmar el cambio.'
          : 'Elige el servicio y el horario que mejor se adapten a ti.'
      }
      action={
        <Link href="/mis-citas" className="text-link">
          Ver mis citas <ArrowRight size={16} />
        </Link>
      }
    >
      <Steps step={review ? 1 : 0} />
      {appointment && (
        <Notice>
          Horario actual: {appointment.slot.service.name} · {dateLabel(appointment.slot.starts_at)}{' '}
          · {timeLabel(appointment.slot.starts_at)}
        </Notice>
      )}
      <div className="booking-grid">
        <section
          className="panel booking-panel"
          aria-label={review ? 'Revisar reserva' : 'Elegir horario'}
        >
          {review && selected ? (
            <>
              <button className="text-link back-link" onClick={() => setReview(false)}>
                <ArrowLeft size={16} />
                Elegir otro horario
              </button>
              <h2>Revisa los detalles</h2>
              <p className="muted">
                Todo listo para {appointment ? 'actualizar tu horario' : 'crear tu cita de prueba'}.
                Confirma que los datos son correctos.
              </p>
              <dl className="review-details">
                <div>
                  <dt>Servicio</dt>
                  <dd>{service?.name}</dd>
                </div>
                <div>
                  <dt>Fecha</dt>
                  <dd>
                    {dateLabel(selected.starts_at, {
                      weekday: 'long',
                      day: 'numeric',
                      month: 'long',
                      year: 'numeric',
                    })}
                  </dd>
                </div>
                <div>
                  <dt>Horario</dt>
                  <dd>
                    {timeLabel(selected.starts_at)} – {timeLabel(selected.ends_at)}
                  </dd>
                </div>
                <div>
                  <dt>Cuenta ficticia</dt>
                  <dd>{auth.user?.alias}</dd>
                </div>
              </dl>
              <Notice>
                {appointment
                  ? 'El código de reserva se conserva. Si el horario se ocupa, mantendremos tu cita original.'
                  : 'Solo al confirmar se guardará la reserva. Este laboratorio no genera citas médicas reales.'}
              </Notice>
              <div className="button-row">
                <button className="button" disabled={form.processing} onClick={submit}>
                  {form.processing
                    ? 'Confirmando…'
                    : appointment
                      ? 'Confirmar cambio'
                      : 'Confirmar reserva'}
                  <Check size={17} />
                </button>
                <button
                  className="button secondary"
                  disabled={form.processing}
                  onClick={() => setReview(false)}
                >
                  Volver
                </button>
              </div>
            </>
          ) : (
            <>
              <div className="field service-filter">
                <label htmlFor="service">Servicio de prueba</label>
                <select
                  id="service"
                  value={filters.service_id ?? ''}
                  onChange={(e) => filter({ service_id: Number(e.target.value) })}
                  disabled={!!appointment}
                >
                  {services.map((s) => (
                    <option key={s.id} value={s.id}>
                      {s.name}
                    </option>
                  ))}
                </select>
                {service && (
                  <span className="field-hint">
                    {service.description} · {service.duration_minutes} minutos
                  </span>
                )}
              </div>
              <div className="calendar-slots">
                <div className="calendar">
                  <div className="calendar-header">
                    <h2>{dateLabel(filters.month + '-01', { month: 'long', year: 'numeric' })}</h2>
                    <div>
                      <button
                        className="icon-button"
                        onClick={() => moveMonth(-1)}
                        aria-label="Mes anterior"
                      >
                        <ChevronLeft size={18} />
                      </button>
                      <button
                        className="icon-button"
                        onClick={() => moveMonth(1)}
                        aria-label="Mes siguiente"
                      >
                        <ChevronRight size={18} />
                      </button>
                    </div>
                  </div>
                  <div className="calendar-week" aria-hidden="true">
                    {['L', 'M', 'M', 'J', 'V', 'S', 'D'].map((d, i) => (
                      <span key={i}>{d}</span>
                    ))}
                  </div>
                  <div className="calendar-days" role="group" aria-label="Seleccionar fecha">
                    {Array.from({ length: offset }, (_, i) => (
                      <span key={'blank' + i} />
                    ))}
                    {Array.from({ length: total }, (_, i) => {
                      const key = `${filters.month}-${String(i + 1).padStart(2, '0')}`;
                      return (
                        <button
                          key={key}
                          disabled={key < today}
                          className={`${key === filters.date ? 'selected ' : ''}${availableDates.includes(key) ? 'has-slots' : ''}`}
                          aria-label={
                            dateLabel(key, { weekday: 'long', day: 'numeric', month: 'long' }) +
                            (availableDates.includes(key) ? ', con disponibilidad' : '')
                          }
                          aria-pressed={key === filters.date}
                          onClick={() => filter({ date: key })}
                        >
                          {i + 1}
                          <span />
                        </button>
                      );
                    })}
                  </div>
                  <p className="calendar-legend">
                    <span className="status-dot" />
                    Días con horarios disponibles
                  </p>
                </div>
                <div className="slots-column">
                  <div className="slots-heading">
                    <h2>
                      {dateLabel(filters.date, {
                        weekday: 'short',
                        day: 'numeric',
                        month: 'short',
                      })}
                    </h2>
                    <span>
                      {slots.length} {slots.length === 1 ? 'horario' : 'horarios'}
                    </span>
                  </div>
                  {slots.length ? (
                    <>
                      <p className="slot-period">
                        <Clock3 size={14} />
                        Hora de Colombia
                      </p>
                      <div className="slot-list" role="group" aria-label="Horarios disponibles">
                        {slots.map((s) => (
                          <button
                            key={s.id}
                            className={`slot-button ${selected?.id === s.id ? 'selected' : ''}`}
                            aria-pressed={selected?.id === s.id}
                            onClick={() => setSelected(s)}
                          >
                            <span>{timeLabel(s.starts_at)}</span>
                            <span>
                              {selected?.id === s.id ? (
                                <>
                                  <Check size={14} />
                                  Seleccionado
                                </>
                              ) : (
                                'Disponible'
                              )}
                            </span>
                          </button>
                        ))}
                      </div>
                    </>
                  ) : (
                    <Empty title="Sin horarios disponibles">
                      Prueba otra fecha del calendario o selecciona otro servicio.
                    </Empty>
                  )}
                </div>
              </div>
              <div className="panel-foot">
                <ShieldCheck size={16} />
                Elegir un horario no reserva. Lo confirmarás en el siguiente paso.
              </div>
            </>
          )}
        </section>
        <aside className="selection-panel panel">
          <div className="selection-heading">
            <CalendarDays size={19} />
            <h2>{review ? 'Tu reserva de prueba' : 'Tu selección'}</h2>
          </div>
          <div className="selection-service">{service?.name ?? 'Selecciona un servicio'}</div>
          {selected ? (
            <>
              <div className="selection-detail">
                <CalendarDays size={18} />
                <div>
                  <span>Fecha</span>
                  <strong>{dateLabel(selected.starts_at)}</strong>
                </div>
              </div>
              <div className="selection-detail">
                <Clock3 size={18} />
                <div>
                  <span>Hora</span>
                  <strong>
                    {timeLabel(selected.starts_at)} – {timeLabel(selected.ends_at)}
                  </strong>
                </div>
              </div>
              <div className="selection-account">
                <span className="avatar small">{auth.user?.alias.replace('DEMO-', '')[0]}</span>
                <span>
                  {auth.user?.alias}
                  <small>Cuenta ficticia</small>
                </span>
              </div>
            </>
          ) : (
            <div className="selection-placeholder">
              <CalendarDays size={26} />
              <p>Elige una fecha y un horario para ver aquí tu resumen.</p>
            </div>
          )}
          <Notice>Aún no hay una reserva. Revisa los datos antes de confirmar.</Notice>
          {!review && (
            <button
              className="button full"
              disabled={!selected}
              onClick={() => {
                setReview(true);
                window.scrollTo({ top: 0, behavior: 'instant' });
              }}
            >
              Revisar horario
              <ArrowRight size={16} />
            </button>
          )}
          <p className="selection-hint">
            {appointment
              ? 'Puedes volver a tus citas sin hacer cambios.'
              : 'Podrás reprogramar o cancelar desde Mis citas.'}
          </p>
        </aside>
      </div>
    </Layout>
  );
}
