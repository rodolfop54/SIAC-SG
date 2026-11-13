import { router } from '@inertiajs/react';
import { useState } from 'react';
import { ChartNoAxesCombined, Download } from 'lucide-react';
import Layout from '../components/Layout';
import { Empty } from '../components/UI';
import { dateLabel } from '../types';
import type { Service } from '../types';
type Props = {
  filters: { from: string; to: string; service_id: string };
  services: Service[];
  byStatus: { status: string; total: number }[];
  byService: { name: string; total: number; confirmed: number; cancelled: number }[];
  byDay: { date: string; total: number }[];
};
export default function Reports({ filters, services, byStatus, byService, byDay }: Props) {
  const [values, setValues] = useState(filters);
  const total = byStatus.reduce((n, s) => n + s.total, 0);
  const confirmed = byStatus.find((s) => s.status === 'CONFIRMADA')?.total ?? 0;
  const max = Math.max(1, ...byDay.map((d) => d.total));
  function download() {
    const rows = [
      ['Servicio', 'Total', 'Confirmadas', 'Canceladas'],
      ...byService.map((s) => [s.name, s.total, s.confirmed, s.cancelled]),
    ];
    const a = document.createElement('a');
    const url = URL.createObjectURL(
      new Blob(
        [
          '\uFEFF' +
            rows
              .map((r) => r.map((c) => '"' + String(c).replaceAll('"', '""') + '"').join(','))
              .join('\r\n'),
        ],
        { type: 'text/csv;charset=utf-8' },
      ),
    );
    a.href = url;
    a.download = `siac-sg-${filters.from}-${filters.to}.csv`;
    a.click();
    URL.revokeObjectURL(url);
  }
  return (
    <Layout
      title="Reportes"
      description="Actividad de citas ficticias, calculada desde la base de datos."
      action={
        <button className="button secondary" onClick={download} disabled={!total}>
          <Download size={16} />
          Exportar CSV
        </button>
      }
    >
      <form
        className="filter-bar panel"
        onSubmit={(e) => {
          e.preventDefault();
          router.get('/admin/reportes', values);
        }}
      >
        <div className="field">
          <label htmlFor="from">Desde</label>
          <input
            id="from"
            type="date"
            required
            value={values.from}
            onChange={(e) => setValues({ ...values, from: e.target.value })}
          />
        </div>
        <div className="field">
          <label htmlFor="to">Hasta</label>
          <input
            id="to"
            type="date"
            required
            min={values.from}
            value={values.to}
            onChange={(e) => setValues({ ...values, to: e.target.value })}
          />
        </div>
        <div className="field">
          <label htmlFor="report-service">Servicio</label>
          <select
            id="report-service"
            value={values.service_id}
            onChange={(e) => setValues({ ...values, service_id: e.target.value })}
          >
            <option value="">Todos los servicios</option>
            {services.map((s) => (
              <option key={s.id} value={s.id}>
                {s.name}
              </option>
            ))}
          </select>
        </div>
        <button className="button">Aplicar filtros</button>
      </form>
      <p className="page-note">
        Periodo según la fecha programada de la cita, del {dateLabel(filters.from)} al{' '}
        {dateLabel(filters.to)}. Incluye citas canceladas.
      </p>
      <div className="report-totals">
        <div>
          <span>Total de citas</span>
          <strong>{total}</strong>
        </div>
        <div>
          <span>
            <i className="status-dot" />
            Confirmadas
          </span>
          <strong>{confirmed}</strong>
        </div>
        <div>
          <span>
            <i className="status-dot gray" />
            Canceladas
          </span>
          <strong>{total - confirmed}</strong>
        </div>
      </div>
      {total ? (
        <>
          <section className="panel report-chart">
            <h2>Citas por fecha</h2>
            <p className="muted">Distribución dentro del periodo seleccionado</p>
            <div
              className="bar-chart"
              tabIndex={0}
              role="img"
              aria-label={byDay.map((d) => `${dateLabel(d.date)}: ${d.total} citas`).join('. ')}
            >
              {byDay.map((d) => (
                <div className="bar-item" key={d.date}>
                  <strong>{d.total}</strong>
                  <div className="bar-track">
                    <div style={{ height: Math.max(4, (d.total / max) * 100) + '%' }} />
                  </div>
                  <span>{dateLabel(d.date, { day: 'numeric', month: 'short' })}</span>
                </div>
              ))}
            </div>
          </section>
          <section className="panel">
            <div className="section-heading">
              <h2>Detalle por servicio</h2>
              <ChartNoAxesCombined size={20} />
            </div>
            <div
              className="table-region"
              role="region"
              aria-label="Reporte por servicio"
              tabIndex={0}
            >
              <table>
                <thead>
                  <tr>
                    <th>Servicio</th>
                    <th>Total</th>
                    <th>Confirmadas</th>
                    <th>Canceladas</th>
                  </tr>
                </thead>
                <tbody>
                  {byService.map((s) => (
                    <tr key={s.name}>
                      <td>
                        <strong>{s.name}</strong>
                      </td>
                      <td>{s.total}</td>
                      <td>{s.confirmed}</td>
                      <td>{s.cancelled}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </section>
        </>
      ) : (
        <div className="panel">
          <Empty title="Sin citas en este periodo">
            Amplía las fechas o cambia el servicio para consultar otra actividad.
          </Empty>
        </div>
      )}
    </Layout>
  );
}
