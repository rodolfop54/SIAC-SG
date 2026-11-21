import { Link, useForm } from '@inertiajs/react';
import Layout from '../components/Layout';
import { FieldError } from '../components/UI';

type ProfileData = {
  name: string;
  email: string | null;
  emailVerified: boolean;
  document: string | null;
  hasPassword: boolean;
};

export default function Profile({
  profile,
  emailEnabled,
  freshAccess,
  canConfirmByEmail,
  accessChallenge,
  pendingEmail,
}: {
  profile: ProfileData;
  emailEnabled: boolean;
  freshAccess: boolean;
  canConfirmByEmail: boolean;
  accessChallenge: boolean;
  pendingEmail: string | null;
}) {
  const name = useForm({ name: profile.name });
  const email = useForm({ email: '', current_password: '' });
  const verify = useForm({ code: '' });
  const authorize = useForm({});
  const access = useForm({ access_code: '' });
  return (
    <Layout title="Mi perfil" description="Actualiza tu nombre y el correo de tu perfil.">
      <div className="panel profile-panel">
        <section className="profile-section" aria-labelledby="profile-name-title">
          <div className="profile-section-copy">
            <h2 id="profile-name-title">Datos personales</h2>
            <p className="muted">Usa un nombre de prueba para este laboratorio.</p>
          </div>
          <form
            className="profile-form"
            onSubmit={(e) => {
              e.preventDefault();
              name.patch('/perfil');
            }}
          >
            <div className="field">
              <label htmlFor="profile-name">Nombre completo ficticio</label>
              <input
                id="profile-name"
                autoComplete="name"
                required
                minLength={3}
                maxLength={100}
                value={name.data.name}
                onChange={(e) => name.setData('name', e.target.value)}
                aria-invalid={!!name.errors.name}
                aria-describedby="profile-name-error"
              />
              <FieldError id="profile-name-error" message={name.errors.name} />
            </div>
            {profile.document && (
              <p className="profile-document">
                Documento vinculado: <strong>{profile.document}</strong>
                <span className="muted">
                  El documento conserva la relación con tus citas y no se modifica desde el perfil.
                </span>
              </p>
            )}
            <div>
              <button className="button" disabled={name.processing}>
                {name.processing ? 'Guardando…' : 'Guardar nombre'}
              </button>
            </div>
          </form>
        </section>
        <section className="profile-section" aria-labelledby="profile-email-title">
          <div className="profile-section-copy">
            <h2 id="profile-email-title">
              {profile.document ? 'Correo de acceso' : 'Correo del perfil'}
            </h2>
            <p className="muted">Tu dirección actual sigue activa hasta que verifiques la nueva.</p>
          </div>
          <div className="profile-form">
            <div className="profile-current-email">
              <span>Correo actual</span>
              <strong>{profile.email ?? 'Todavía no has asociado un correo'}</strong>
              {profile.email && (
                <span className={profile.emailVerified ? 'profile-verified' : 'muted'}>
                  {profile.emailVerified ? 'Verificado' : 'Pendiente de verificación'}
                </span>
              )}
            </div>
            {!emailEnabled && (
              <p className="notice">
                El cambio de correo requiere que el servicio de envío esté activado.
              </p>
            )}
            {!freshAccess && !pendingEmail && (
              <div className="profile-access-check">
                <p>
                  Para cambiar el correo, confirma tu acceso con la contraseña actual
                  {canConfirmByEmail ? ' o con un código enviado a tu correo actual' : ''}.
                </p>
                {canConfirmByEmail &&
                  (accessChallenge ? (
                    <form
                      className="profile-form"
                      onSubmit={(e) => {
                        e.preventDefault();
                        access.post('/perfil/confirmar-acceso/verificar', {
                          onFinish: () => access.reset('access_code'),
                        });
                      }}
                    >
                      <div className="field">
                        <label htmlFor="profile-access-code">Código del correo actual</label>
                        <input
                          id="profile-access-code"
                          autoComplete="one-time-code"
                          inputMode="numeric"
                          pattern="[0-9]{6}"
                          required
                          maxLength={6}
                          value={access.data.access_code}
                          onChange={(e) => access.setData('access_code', e.target.value)}
                          aria-invalid={!!access.errors.access_code}
                          aria-describedby="profile-access-error"
                        />
                        <FieldError id="profile-access-error" message={access.errors.access_code} />
                      </div>
                      <div>
                        <button className="button secondary" disabled={access.processing}>
                          Confirmar acceso
                        </button>
                      </div>
                    </form>
                  ) : (
                    <button
                      className="button secondary"
                      disabled={authorize.processing || !emailEnabled}
                      onClick={() => authorize.post('/perfil/confirmar-acceso')}
                    >
                      {authorize.processing ? 'Enviando…' : 'Recibir código en mi correo actual'}
                    </button>
                  ))}
              </div>
            )}
            {pendingEmail ? (
              <form
                className="profile-form"
                onSubmit={(e) => {
                  e.preventDefault();
                  verify.post('/perfil/correo/verificar', { onFinish: () => verify.reset('code') });
                }}
              >
                <p className="profile-pending-email">
                  Introduce el código enviado a <strong>{pendingEmail}</strong>. Vence en diez
                  minutos.
                </p>
                <div className="field">
                  <label htmlFor="profile-email-code">Código del nuevo correo</label>
                  <input
                    id="profile-email-code"
                    autoComplete="one-time-code"
                    inputMode="numeric"
                    pattern="[0-9]{6}"
                    required
                    maxLength={6}
                    value={verify.data.code}
                    onChange={(e) => verify.setData('code', e.target.value)}
                    aria-invalid={!!verify.errors.code}
                    aria-describedby="profile-email-code-error"
                  />
                  <FieldError id="profile-email-code-error" message={verify.errors.code} />
                </div>
                <div className="profile-actions">
                  <button className="button" disabled={verify.processing}>
                    {verify.processing ? 'Verificando…' : 'Verificar nuevo correo'}
                  </button>
                  <Link
                    href="/perfil/correo/cancelar"
                    method="post"
                    as="button"
                    type="button"
                    className="button secondary"
                  >
                    Cancelar cambio
                  </Link>
                </div>
              </form>
            ) : (
              <form
                className="profile-form"
                onSubmit={(e) => {
                  e.preventDefault();
                  email.post('/perfil/correo', { onFinish: () => email.reset('current_password') });
                }}
              >
                <div className="field">
                  <label htmlFor="profile-email">Nuevo correo electrónico</label>
                  <input
                    id="profile-email"
                    type="email"
                    required
                    maxLength={254}
                    autoComplete="email"
                    value={email.data.email}
                    onChange={(e) => email.setData('email', e.target.value)}
                    aria-invalid={!!email.errors.email}
                    aria-describedby="profile-email-error"
                  />
                  <FieldError id="profile-email-error" message={email.errors.email} />
                </div>
                {!freshAccess && profile.hasPassword && (
                  <div className="field">
                    <label htmlFor="profile-current-password">Contraseña actual</label>
                    <input
                      id="profile-current-password"
                      type="password"
                      autoComplete="current-password"
                      required
                      maxLength={72}
                      value={email.data.current_password}
                      onChange={(e) => email.setData('current_password', e.target.value)}
                      aria-invalid={!!email.errors.current_password}
                      aria-describedby="profile-password-error"
                    />
                    <FieldError
                      id="profile-password-error"
                      message={email.errors.current_password}
                    />
                  </div>
                )}
                <div>
                  <button
                    className="button"
                    disabled={
                      email.processing || !emailEnabled || (!freshAccess && !profile.hasPassword)
                    }
                  >
                    {email.processing ? 'Enviando…' : 'Enviar código al nuevo correo'}
                  </button>
                </div>
              </form>
            )}
          </div>
        </section>
        <section className="profile-section" aria-labelledby="profile-security-title">
          <div className="profile-section-copy">
            <h2 id="profile-security-title">Contraseña</h2>
            <p className="muted">
              {profile.document
                ? 'Puedes entrar con un código o usar una contraseña propia.'
                : 'Esta cuenta de demostración conserva el acceso por alias y contraseña.'}
            </p>
          </div>
          <div className="profile-form">
            <p>
              {!profile.document
                ? 'La cuenta de demostración sigue usando sus credenciales actuales.'
                : profile.emailVerified
                  ? 'Recibe un enlace en tu correo verificado para elegir una contraseña. Tus citas y tu perfil se conservan.'
                  : 'Asocia y verifica primero un correo para poder crear o recuperar tu contraseña por email.'}
            </p>
            {profile.document && profile.emailVerified && (
              <div>
                <Link href="/recuperar" className="button secondary">
                  {profile.hasPassword ? 'Cambiar o recuperar contraseña' : 'Crear mi contraseña'}
                </Link>
              </div>
            )}
          </div>
        </section>
      </div>
    </Layout>
  );
}
