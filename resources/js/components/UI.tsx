import { Link, usePage } from '@inertiajs/react';
import { AlertCircle, CalendarDays, ChevronLeft, ChevronRight, CheckCircle2 } from 'lucide-react';
import { useEffect, useRef } from 'react';
import type { ReactNode } from 'react';
import type { Paginated, Shared } from '../types';
export function Errors() {
  const { errors } = usePage<Shared>().props;
  const ref = useRef<HTMLDivElement>(null);
  useEffect(() => {
    if (Object.keys(errors).length) ref.current?.focus();
  }, [errors]);
  return Object.keys(errors).length ? (
    <div ref={ref} tabIndex={-1} role="alert" className="alert error">
      <AlertCircle size={20} />
      <div>
        <strong>No pudimos completar la operación</strong>
        {Object.entries(errors).map(([k, v]) => (
          <p key={k}>{v}</p>
        ))}
      </div>
    </div>
  ) : null;
}
export function Notice({ children }: { children: ReactNode }) {
  return (
    <div className="notice">
      <AlertCircle size={17} />
      <span>{children}</span>
    </div>
  );
}
export function Empty({
  title,
  children,
  action,
}: {
  title: string;
  children: ReactNode;
  action?: ReactNode;
}) {
  return (
    <div className="empty">
      <CalendarDays size={30} />
      <h2>{title}</h2>
      <p>{children}</p>
      {action}
    </div>
  );
}
export function Badge({ status }: { status: string }) {
  return (
    <span className={`badge ${status === 'CONFIRMADA' ? 'green' : 'neutral'}`}>
      {status === 'CONFIRMADA' ? <CheckCircle2 size={14} /> : null}
      {status === 'CONFIRMADA' ? 'Confirmada' : status === 'CANCELADA' ? 'Cancelada' : status}
    </span>
  );
}
export function Pagination({ data }: { data: Omit<Paginated<unknown>, 'data'> }) {
  return data.last_page > 1 ? (
    <nav className="pagination" aria-label="Paginación">
      <span>
        Página {data.current_page} de {data.last_page} · {data.total} registros
      </span>
      <div>
        {data.prev_page_url && (
          <Link className="button secondary" href={data.prev_page_url}>
            <ChevronLeft size={16} />
            Anterior
          </Link>
        )}
        {data.next_page_url && (
          <Link className="button secondary" href={data.next_page_url}>
            Siguiente
            <ChevronRight size={16} />
          </Link>
        )}
      </div>
    </nav>
  ) : null;
}
export function Steps({ step }: { step: number }) {
  return (
    <ol className="steps" aria-label="Progreso de reserva">
      {['Elegir horario', 'Revisar datos', 'Confirmación'].map((s, i) => (
        <li
          key={s}
          className={i <= step ? 'current' : ''}
          aria-current={i === step ? 'step' : undefined}
        >
          <span>{i < step ? <CheckCircle2 size={16} /> : i + 1}</span>
          {s}
        </li>
      ))}
    </ol>
  );
}
export function FieldError({ message, id }: { message?: string; id: string }) {
  return message ? (
    <span id={id} className="field-error">
      {message}
    </span>
  ) : null;
}
