import { Head, Link, usePage } from '@inertiajs/react';
import { FlaskConical, ArrowUpRight } from 'lucide-react';
import type { ReactNode } from 'react';
import { Logo } from './Layout';
import type { Shared } from '../types';

export default function PublicLayout({
  title,
  children,
  chat = false,
}: {
  title: string;
  children: ReactNode;
  chat?: boolean;
}) {
  const { auth } = usePage<Shared>().props;
  return (
    <>
      <Head title={title} />
      <a className="skip-link" href="#main">
        Saltar al contenido
      </a>
      <div className={`public-shell ${chat ? 'public-chat-shell' : ''}`}>
        <header className="public-header">
          <Link href="/" aria-label="SIAC-SG, inicio">
            <Logo />
          </Link>
          <nav className="public-nav" aria-label="Navegación pública">
            <Link href="/bot" aria-current={chat ? 'page' : undefined}>
              Bot público
            </Link>
            {!auth.user && <Link href="/registro">Registrarme</Link>}
            <Link
              className="button secondary"
              href={
                auth.user
                  ? auth.user.role === 'admin'
                    ? '/admin/agenda'
                    : '/mis-citas'
                  : '/acceso'
              }
            >
              {auth.user ? 'Mi espacio' : 'Ingresar'}
              <ArrowUpRight size={15} />
            </Link>
          </nav>
        </header>
        <div className="simulation-banner">
          <FlaskConical size={15} />
          <span>
            Laboratorio académico · Usa nombres y documentos ficticios · Sin conexión a servicios de
            salud reales
          </span>
        </div>
        {children}
        <footer className="public-footer">
          SIAC-SG · UNAD · Ninguna reserva corresponde a una cita médica real.
        </footer>
      </div>
    </>
  );
}
