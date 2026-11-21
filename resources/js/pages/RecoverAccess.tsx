import { Link, useForm, usePage } from '@inertiajs/react';
import PublicLayout from '../components/PublicLayout';
import DocumentFields from '../components/DocumentFields';
import { Errors } from '../components/UI';
import type { Shared } from '../types';

export default function RecoverAccess({
  documentTypes,
  emailEnabled,
}: {
  documentTypes: Record<string, string>;
  emailEnabled: boolean;
}) {
  const form = useForm({ document_type: 'CC', document_number: '' });
  const { flash } = usePage<Shared>().props;
  return (
    <PublicLayout title="Crear o recuperar contraseña">
      <main id="main" className="login-main">
        <section className="login-copy">
          <h1>Recupera tu acceso.</h1>
          <p>
            Recibirás un enlace en el correo verificado de tu cuenta para elegir una contraseña.
          </p>
        </section>
        <section className="login-panel">
          <h2>Crear o recuperar contraseña</h2>
          <Errors />
          {flash.success && (
            <p className="notice" role="status">
              {flash.success}
            </p>
          )}
          {!emailEnabled && (
            <p className="notice" role="status">
              El envío de correos está pendiente de activación.
            </p>
          )}
          <form
            onSubmit={(e) => {
              e.preventDefault();
              form.post('/recuperar');
            }}
          >
            <DocumentFields
              types={documentTypes}
              type={form.data.document_type}
              number={form.data.document_number}
              error={form.errors.document_number}
              onType={(v) => form.setData('document_type', v)}
              onNumber={(v) => form.setData('document_number', v)}
            />
            <button className="button full" disabled={form.processing || !emailEnabled}>
              Enviar enlace
            </button>
          </form>
          <p>
            <Link href="/acceso" className="text-link">
              Volver al acceso
            </Link>
          </p>
          <p>
            Si tu cuenta anterior aún no tiene correo, entra con tu clave y selecciona “Configurar
            acceso por correo” en el bot.
          </p>
        </section>
      </main>
    </PublicLayout>
  );
}
