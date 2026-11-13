import { Head, Link, usePage } from '@inertiajs/react';
import {
  ArrowRight,
  CalendarDays,
  CheckCircle2,
  Clock3,
  MessageCircle,
  ShieldCheck,
  FlaskConical,
} from 'lucide-react';
import { Logo } from '../components/Layout';
import type { Shared } from '../types';
export default function Home() {
  const { auth } = usePage<Shared>().props;
  const target = auth.user
    ? auth.user.role === 'admin'
      ? '/admin/agenda'
      : '/disponibilidad'
    : '/acceso';
  return (
    <>
      <Head title="Tus citas, en un solo lugar" />
      <a className="skip-link" href="#main">
        Saltar al contenido
      </a>
      <div className="public-shell">
        <header className="public-header">
          <Logo />
          <Link href={target} className="button secondary">
            {auth.user ? 'Mi espacio' : 'Acceder'}
            <ArrowRight size={16} />
          </Link>
        </header>
        <div className="simulation-banner">
          <FlaskConical size={15} />
          Laboratorio académico · Datos ficticios · Sin conexión a instituciones de salud
        </div>
        <main id="main" className="home-main">
          <div className="home-intro">
            <h1>
              Tus citas,
              <br />
              <span>en un solo lugar.</span>
            </h1>
            <p>
              Encuentra un horario, confirma tu reserva y organiza tus citas de prueba. Con
              claridad, a tu ritmo.
            </p>
            <div className="button-row">
              <Link href={target} className="button">
                Entrar al laboratorio <ArrowRight size={18} />
              </Link>
              <Link href="/bot" className="button secondary">
                <MessageCircle size={18} />
                Reservar por el bot público
              </Link>
            </div>
            <p className="home-note">
              <ShieldCheck size={16} />
              Un entorno seguro para aprender con información sintética.
            </p>
          </div>
          <div className="journey-panel">
            <div className="journey-heading">
              <CalendarDays size={23} />
              <h2>Una gestión más sencilla</h2>
            </div>
            {[
              [
                CalendarDays,
                'Encuentra tu horario',
                'Consulta la disponibilidad por servicio y fecha.',
              ],
              [CheckCircle2, 'Confirma con tranquilidad', 'Revisa los detalles antes de reservar.'],
              [Clock3, 'Gestiona tus citas', 'Reprograma o cancela desde tu espacio.'],
            ].map(([Icon, title, text], i) => {
              const I = Icon as typeof CalendarDays;
              return (
                <div className="journey-row" key={i}>
                  <span className="journey-icon">
                    <I size={21} />
                  </span>
                  <div>
                    <h3>{String(title)}</h3>
                    <p>{String(text)}</p>
                  </div>
                  <span className="journey-number">{i + 1}</span>
                </div>
              );
            })}
            <div className="journey-footer">
              <span className="status-dot" />
              Web y bot, una misma agenda
            </div>
          </div>
        </main>
        <footer className="public-footer">
          <p>
            Este prototipo no presta atención médica. Ninguna reserva corresponde a una cita real.
          </p>
          <span>SIAC-SG · Ingeniería de Sistemas · UNAD · Fase 4</span>
        </footer>
      </div>
    </>
  );
}
