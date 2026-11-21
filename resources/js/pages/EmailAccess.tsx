import { Link, useForm, usePage } from '@inertiajs/react';
import PublicLayout from '../components/PublicLayout';
import DocumentFields from '../components/DocumentFields';
import { Errors } from '../components/UI';
import type { Shared } from '../types';

export default function EmailAccess({
  documentTypes,
  signup,
  challenge,
  emailEnabled,
}: {
  documentTypes: Record<string, string>;
  signup: boolean;
  challenge: boolean;
  emailEnabled: boolean;
}) {
  const { flash } = usePage<Shared>().props;
  const request = useForm({
    document_type: 'CC',
    document_number: '',
    name: '',
    email: '',
    current_password: '',
  });
  const verify = useForm({ code: '' });
  return (
    <PublicLayout title="Acceso por correo">
      <main id="main" className="login-main">
        <section className="login-copy">
          <h1>Una cuenta para tus citas.</h1>
          <p>
            Entra en el bot y el portal con el mismo documento. Verifica tu correo para acceder sin
            recordar una contraseña.
          </p>
          <Link href="/acceso" className="text-link">
            Prefiero usar mi contraseña
          </Link>
        </section>
        <section className="login-panel">
          <h2>
            {challenge ? 'Verifica tu correo' : signup ? 'Crea tu cuenta' : 'Entrar con un código'}
          </h2>
          <Errors />
          {flash.success && (
            <p className="notice" role="status">
              {flash.success}
            </p>
          )}
          {!emailEnabled && (
            <p className="notice" role="status">
              El acceso por correo está pendiente de activación. Puedes entrar con tu contraseña
              actual.
            </p>
          )}
          {challenge ? (
            <form
              onSubmit={(e) => {
                e.preventDefault();
                verify.post('/acceso/codigo/verificar', { onFinish: () => verify.reset('code') });
              }}
            >
              <p>
                Introduce el código recibido. Vence en diez minutos y se puede usar una sola vez.
              </p>
              <div className="field">
                <label htmlFor="access-code">Código de acceso</label>
                <input
                  id="access-code"
                  required
                  inputMode="numeric"
                  autoComplete="one-time-code"
                  pattern="[0-9]{6}"
                  maxLength={6}
                  value={verify.data.code}
                  onChange={(e) => verify.setData('code', e.target.value)}
                />
              </div>
              <button className="button full" disabled={verify.processing}>
                Verificar y entrar
              </button>
              <Link href="/acceso/codigo/reiniciar" method="post" as="button" className="text-link">
                Solicitar otro código o cambiar los datos
              </Link>
            </form>
          ) : (
            <form
              onSubmit={(e) => {
                e.preventDefault();
                request.post('/acceso/codigo', {
                  onFinish: () => request.reset('current_password'),
                });
              }}
            >
              <p>
                {signup
                  ? 'Usa un nombre y documento ficticios y un correo que puedas verificar.'
                  : 'Recibirás el código en el correo verificado de tu cuenta.'}
              </p>
              <DocumentFields
                types={documentTypes}
                type={request.data.document_type}
                number={request.data.document_number}
                error={request.errors.document_number}
                onType={(v) => request.setData('document_type', v)}
                onNumber={(v) => request.setData('document_number', v)}
              />
              {signup && (
                <div className="field">
                  <label htmlFor="patient-name">Nombre completo ficticio</label>
                  <input
                    id="patient-name"
                    required
                    minLength={3}
                    maxLength={100}
                    value={request.data.name}
                    onChange={(e) => request.setData('name', e.target.value)}
                  />
                </div>
              )}
              {signup ? (
                <div className="field">
                  <label htmlFor="patient-email">Correo de acceso</label>
                  <input
                    id="patient-email"
                    type="email"
                    required
                    maxLength={254}
                    autoComplete="email"
                    value={request.data.email}
                    onChange={(e) => request.setData('email', e.target.value)}
                  />
                </div>
              ) : (
                <details>
                  <summary>Asociar un correo a mi cuenta anterior</summary>
                  <div className="field">
                    <label htmlFor="enroll-email">Correo que quiero verificar</label>
                    <input
                      id="enroll-email"
                      type="email"
                      maxLength={254}
                      value={request.data.email}
                      onChange={(e) => request.setData('email', e.target.value)}
                    />
                  </div>
                  <div className="field">
                    <label htmlFor="existing-password">
                      Contraseña actual, si aún no he iniciado sesión
                    </label>
                    <input
                      id="existing-password"
                      type="password"
                      autoComplete="current-password"
                      maxLength={72}
                      value={request.data.current_password}
                      onChange={(e) => request.setData('current_password', e.target.value)}
                    />
                  </div>
                </details>
              )}
              <button className="button full" disabled={request.processing || !emailEnabled}>
                {request.processing ? 'Solicitando…' : 'Recibir código'}
              </button>
              <p className="registration-login">
                <Link
                  href={signup ? '/acceso/codigo' : '/acceso/codigo?registro=1'}
                  className="text-link"
                >
                  {signup ? 'Ya tengo una cuenta' : 'Es mi primera visita'}
                </Link>
              </p>
            </form>
          )}
        </section>
      </main>
    </PublicLayout>
  );
}
