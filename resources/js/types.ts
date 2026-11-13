export type User = { id: number; alias: string; name: string; role: 'user' | 'admin' };
export type Service = { id: number; name: string; description: string; duration_minutes: number };
export type Slot = {
  id: number;
  service_id: number;
  starts_at: string;
  ends_at: string;
  enabled: boolean;
  service?: Service;
  active_count?: number;
};
export type Appointment = {
  id: number;
  code: string;
  status: 'CONFIRMADA' | 'CANCELADA';
  slot_id: number;
  slot: Slot & { service: Service };
  created_at: string;
  channel: string;
};
export type Shared = {
  auth: { user: User | null };
  flash: { success?: string };
  errors: Record<string, string>;
  today: string;
  [key: string]: unknown;
};
export type Paginated<T> = {
  data: T[];
  total: number;
  current_page: number;
  last_page: number;
  prev_page_url: string | null;
  next_page_url: string | null;
};
export const dateLabel = (
  value: string,
  opts: Intl.DateTimeFormatOptions = { day: 'numeric', month: 'long', year: 'numeric' },
) =>
  new Intl.DateTimeFormat('es-CO', { timeZone: 'America/Bogota', ...opts }).format(
    new Date(value.length === 10 ? value + 'T12:00:00-05:00' : value),
  );
export const timeLabel = (value: string) =>
  dateLabel(value, { hour: '2-digit', minute: '2-digit', hour12: false });
