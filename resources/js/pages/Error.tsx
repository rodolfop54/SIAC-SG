import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, ShieldAlert, FlaskConical } from 'lucide-react';
import { Logo } from '../components/Layout';
const messages: Record<number, [string, string]> = {
  403: [
    'Este espacio requiere otro permiso',
    'Tu cuenta no tiene acceso a esta información. Vuelve a tu espacio para gestionar tus propias citas.',
  ],
  404: [
    'No encontramos esta página',
    'La dirección no existe o el registro ya no está disponible. Puedes volver al inicio.',
  ],
  419: [
    'La sesión necesita renovarse',
    'Vuelve a iniciar sesión antes de confirmar. Tu solicitud no creó ninguna reserva.',
  ],
  422: [
    'Esta operación ya no está disponible',
    'Revisa el estado de la cita y elige una de las acciones disponibles en tu espacio.',
  ],
  500: [
    'No pudimos completar la solicitud',
    'Intenta de nuevo en un momento. Consulta Mis citas antes de repetir una reserva.',
  ],
  503: ['El laboratorio está en mantenimiento', 'Vuelve a intentar en unos minutos.'],
};
export default function Error({ status }: { status: number }) {
  const [title, text] = messages[status] ?? messages[500];
  return (
    <>
      <Head title={title} />
      <div className="public-shell">
        <header className="public-header">
          <Link href="/">
            <Logo />
          </Link>
        </header>
        <div className="simulation-banner">
          <FlaskConical size={15} />
          Datos ficticios · Sin conexión a servicios sanitarios reales
        </div>
        <main className="error-main">
          <ShieldAlert size={40} />
          <h1>{title}</h1>
          <p>{text}</p>
          <Link className="button" href={status === 419 ? '/acceso?ended=1' : '/'}>
            <ArrowLeft size={16} />
            {status === 419 ? 'Iniciar sesión' : 'Volver al inicio'}
          </Link>
        </main>
        <footer className="public-footer">SIAC-SG · Laboratorio académico · UNAD</footer>
      </div>
    </>
  );
}
