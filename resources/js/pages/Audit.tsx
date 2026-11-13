import { router } from '@inertiajs/react';
import { History } from 'lucide-react';
import Layout from '../components/Layout';
import { Empty, Pagination } from '../components/UI';
import { dateLabel } from '../types';
import type { Paginated } from '../types';
type Event = {
  id: number;
  actor: { alias: string };
  operation: string;
  entity: string;
  entity_id: number;
  before: Record<string, unknown> | null;
  after: Record<string, unknown>;
  channel: string;
  created_at: string;
};
const labels: Record<string, string> = {
  reserva: 'Reserva',
  reprogramacion: 'Reprogramación',
  cancelacion: 'Cancelación',
  crear_franja: 'Crear franja',
  bloquear_franja: 'Bloquear franja',
  habilitar_franja: 'Habilitar franja',
};
export default function Audit({
  events,
  operation,
}: {
  events: Paginated<Event>;
  operation: string;
}) {
  return (
    <Layout
      title="Auditoría"
      description="Trazabilidad de reservas y cambios de agenda. Cada evento conserva su actor y sus valores."
    >
      <div className="panel">
        <div className="filter-bar">
          <div className="field">
            <label htmlFor="operation">Operación</label>
            <select
              id="operation"
              value={operation}
              onChange={(e) => router.get('/admin/auditoria', { operation: e.target.value })}
            >
              <option value="">Todas las operaciones</option>
              {Object.entries(labels).map(([k, v]) => (
                <option key={k} value={k}>
                  {v}
                </option>
              ))}
            </select>
          </div>
          <span className="filter-count">
            <History size={17} />
            {events.total} eventos registrados
          </span>
        </div>
        {events.data.length ? (
          <div
            className="table-region"
            role="region"
            aria-label="Eventos de auditoría"
            tabIndex={0}
          >
            <table>
              <thead>
                <tr>
                  <th>Fecha y actor</th>
                  <th>Operación</th>
                  <th>Entidad</th>
                  <th>Canal</th>
                  <th>Cambios</th>
                </tr>
              </thead>
              <tbody>
                {events.data.map((e) => (
                  <tr key={e.id}>
                    <td>
                      {dateLabel(e.created_at, {
                        day: '2-digit',
                        month: 'short',
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: false,
                      })}
                      <small>{e.actor.alias}</small>
                    </td>
                    <td>
                      <strong>{labels[e.operation] ?? e.operation}</strong>
                    </td>
                    <td>
                      {e.entity === 'appointments' ? 'Cita' : 'Franja'} #{e.entity_id}
                    </td>
                    <td>
                      <span className="badge neutral">{e.channel === 'bot' ? 'Bot' : 'Web'}</span>
                    </td>
                    <td>
                      <details>
                        <summary>Ver valores</summary>
                        <div className="audit-values">
                          <strong>Antes</strong>
                          <pre>{JSON.stringify(e.before, null, 2) ?? 'Creación'}</pre>
                          <strong>Después</strong>
                          <pre>{JSON.stringify(e.after, null, 2)}</pre>
                        </div>
                      </details>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        ) : (
          <Empty title="No hay eventos con este filtro">
            Selecciona otra operación para consultar el registro.
          </Empty>
        )}
        <Pagination data={events} />
      </div>
    </Layout>
  );
}
