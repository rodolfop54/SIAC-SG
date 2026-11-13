import { Head, Link, router, usePage } from '@inertiajs/react';
import {
  Activity,
  ArrowUpRight,
  CalendarDays,
  ClipboardList,
  MessageCircle,
  ShieldCheck,
  ChartNoAxesCombined,
  History,
  LogOut,
  Menu,
  X,
  FlaskConical,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import type { ReactNode } from 'react';
import type { Shared } from '../types';
import { Errors } from './UI';
export const Logo = () => (
  <span className="brand">
    <span className="brand-mark">
      <Activity size={25} strokeWidth={2} />
    </span>
    <span>
      SIAC<span className="brand-sg">-SG</span>
      <small>Gestión de citas</small>
    </span>
  </span>
);
export default function Layout({
  title,
  description,
  children,
  action,
}: {
  title: string;
  description?: string;
  children: ReactNode;
  action?: ReactNode;
}) {
  const { auth, flash } = usePage<Shared>().props;
  const { url } = usePage();
  const [open, setOpen] = useState(false);
  useEffect(() => setOpen(false), [url]);
  const links =
    auth.user?.role === 'admin'
      ? ([
          ['/admin/agenda', 'Agenda', CalendarDays],
          ['/admin/auditoria', 'Auditoría', History],
          ['/admin/reportes', 'Reportes', ChartNoAxesCombined],
        ] as const)
      : ([
          ['/disponibilidad', 'Disponibilidad', CalendarDays],
          ['/mis-citas', 'Mis citas', ClipboardList],
          ['/bot', 'Bot público', MessageCircle],
        ] as const);
  return (
    <>
      <Head title={title} />
      <a className="skip-link" href="#main">
        Saltar al contenido
      </a>
      <div className="app-shell">
        <aside className={`sidebar ${open ? 'is-open' : ''}`}>
          <Link href="/" aria-label="SIAC-SG, inicio">
            <Logo />
          </Link>
          <div className="sidebar-label">
            {auth.user?.role === 'admin' ? 'Administración' : 'Mi espacio'}
          </div>
          <nav aria-label="Navegación principal">
            {links.map(([href, label, Icon]) => (
              <Link
                key={href}
                href={href}
                className={`nav-link ${url.startsWith(href) ? 'active' : ''}`}
                aria-current={url.startsWith(href) ? 'page' : undefined}
              >
                <Icon size={19} />
                {label}
                {url.startsWith(href) && <span className="nav-dot" />}
              </Link>
            ))}
          </nav>
          <div className="sidebar-help">
            <FlaskConical size={21} />
            <strong>Un espacio para aprender</strong>
            <p>Todas las cuentas y citas son ficticias. No se presta atención médica.</p>
            <Link href="/">
              Acerca del laboratorio <ArrowUpRight size={15} />
            </Link>
          </div>
          <div className="sidebar-account">
            <span className="avatar">
              {auth.user?.role === 'admin' ? (
                <ShieldCheck size={19} />
              ) : (
                auth.user?.alias.replace('DEMO-', '').slice(0, 1)
              )}
            </span>
            <div>
              <strong>{auth.user?.alias}</strong>
              <small>
                {auth.user?.role === 'admin' ? 'Administrador de prueba' : 'Usuario de prueba'}
              </small>
            </div>
            <button
              className="icon-button"
              onClick={() => router.post('/salir')}
              aria-label="Cerrar sesión"
            >
              <LogOut size={19} />
            </button>
          </div>
        </aside>
        <div className="workspace">
          <header className="topbar">
            <button
              className="icon-button mobile-menu"
              aria-label={open ? 'Cerrar menú' : 'Abrir menú'}
              aria-expanded={open}
              onClick={() => setOpen(!open)}
            >
              {open ? <X /> : <Menu />}
            </button>
            <span className="topbar-context">
              {auth.user?.role === 'admin' ? 'Panel de administración' : 'Portal de usuario'}{' '}
              <span>/</span> <strong>{title}</strong>
            </span>
            <span className="lab-tag">
              <span />
              Laboratorio académico
            </span>
          </header>
          <div className="simulation-banner">
            <FlaskConical size={14} />
            <span>
              Datos ficticios. Sin conexión al Hospital de Sabanagrande ni a servicios de salud
              reales.
            </span>
          </div>
          <main id="main" tabIndex={-1}>
            <div className="page-heading">
              <div>
                <h1>{title}</h1>
                {description && <p>{description}</p>}
              </div>
              {action}
            </div>
            {flash.success && (
              <div role="status" className="alert success">
                {flash.success}
              </div>
            )}
            <Errors />
            {children}
          </main>
          <footer className="app-footer">
            <span>SIAC-SG · Proyecto de grado UNAD</span>
            <span>Hora de Colombia · America/Bogota</span>
          </footer>
        </div>
      </div>
    </>
  );
}
