import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Plus, LockKeyhole, UnlockKeyhole, CalendarDays, X } from 'lucide-react';
import Layout from '../components/Layout';
import { Badge, Empty, Pagination, FieldError } from '../components/UI';
import { dateLabel, timeLabel } from '../types';
import type { Paginated, Service, Slot } from '../types';
export default function Agenda({
  services,
  filters,
  slots,
}: {
  services: Service[];
  filters: { date: string; service_id: string };
  slots: Paginated<Slot>;
}) {
  const [creating, setCreating] = useState(false);
  const form = useForm({
    service_id: String(services[0]?.id ?? ''),
    starts_at: filters.date + 'T09:00',
  });
  const toggle = useForm({ enabled: false });
  return (
    <Layout
      title="Agenda"
      description="Organiza los horarios sintéticos disponibles para tus usuarios."
      action={
        <button className="button" onClick={() => setCreating(!creating)}>
          {creating ? <X size={17} /> : <Plus size={17} />}{' '}
          {creating ? 'Cerrar formulario' : 'Crear franja'}
        </button>
      }
    >
      {creating && (
        <section className="panel create-panel">
          <h2>Crear franja de prueba</h2>
          <p className="muted">
            La duración se calcula según el servicio. No se permiten horarios cruzados.
          </p>
          <form
            onSubmit={(e) => {
              e.preventDefault();
              form.post('/admin/franjas', { onSuccess: () => setCreating(false) });
            }}
            className="create-form"
          >
            <div className="field">
              <label htmlFor="create-service">Servicio</label>
              <select
                id="create-service"
                value={form.data.service_id}
                onChange={(e) => form.setData('service_id', e.target.value)}
              >
                {services.map((s) => (
                  <option key={s.id} value={s.id}>
                    {s.name} · {s.duration_minutes} min
                  </option>
                ))}
              </select>
              <FieldError id="service-error" message={form.errors.service_id} />
            </div>
            <div className="field">
              <label htmlFor="starts">Fecha y hora de Colombia</label>
              <input
                id="starts"
                type="datetime-local"
                required
                value={form.data.starts_at}
                onChange={(e) => form.setData('starts_at', e.target.value)}
                aria-invalid={!!form.errors.starts_at}
              />
              <FieldError id="start-error" message={form.errors.starts_at} />
            </div>
            <button className="button" disabled={form.processing}>
              {form.processing ? 'Guardando…' : 'Guardar franja'}
            </button>
          </form>
        </section>
      )}
      <div className="panel">
        <div className="filter-bar">
          <div className="field">
            <label htmlFor="agenda-date">Fecha</label>
            <input
              id="agenda-date"
              type="date"
              value={filters.date}
              onChange={(e) => {
                if (e.target.value)
                  router.get('/admin/agenda', { ...filters, date: e.target.value });
              }}
            />
          </div>
          <div className="field">
            <label htmlFor="agenda-service">Servicio</label>
            <select
              id="agenda-service"
              value={filters.service_id}
              onChange={(e) =>
                router.get('/admin/agenda', { ...filters, service_id: e.target.value })
              }
            >
              <option value="">Todos los servicios</option>
              {services.map((s) => (
                <option key={s.id} value={s.id}>
                  {s.name}
                </option>
              ))}
            </select>
          </div>
          <span className="filter-count">
            <CalendarDays size={17} />
            {slots.total} franjas
          </span>
        </div>
        {slots.data.length ? (
          <div className="table-region" role="region" aria-label="Franjas de agenda" tabIndex={0}>
            <table>
              <thead>
                <tr>
                  <th>Servicio</th>
                  <th>Fecha y horario</th>
                  <th>Estado</th>
                  <th className="align-right">Acción</th>
                </tr>
              </thead>
              <tbody>
                {slots.data.map((s) => (
                  <tr key={s.id}>
                    <td>
                      <strong>{s.service?.name}</strong>
                      <small>{s.service?.duration_minutes} minutos</small>
                    </td>
                    <td>
                      {dateLabel(s.starts_at, { day: 'numeric', month: 'short', year: 'numeric' })}
                      <small>
                        {timeLabel(s.starts_at)} – {timeLabel(s.ends_at)}
                      </small>
                    </td>
                    <td>
                      <Badge
                        status={s.active_count ? 'Ocupada' : s.enabled ? 'Disponible' : 'Bloqueada'}
                      />
                    </td>
                    <td className="align-right">
                      <button
                        className="button secondary compact"
                        disabled={toggle.processing}
                        onClick={() => {
                          toggle.transform(() => ({ enabled: !s.enabled }));
                          toggle.patch(`/admin/franjas/${s.id}`, { preserveScroll: true });
                        }}
                      >
                        {s.enabled ? <LockKeyhole size={14} /> : <UnlockKeyhole size={14} />}{' '}
                        {s.enabled ? 'Bloquear' : 'Habilitar'}
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        ) : (
          <Empty title="Sin franjas para esta fecha">
            Selecciona otro día o crea una nueva franja.
          </Empty>
        )}
        <Pagination data={slots} />
      </div>
      <p className="page-note">Las franjas ocupadas conservan su reserva y no pueden bloquearse.</p>
    </Layout>
  );
}
