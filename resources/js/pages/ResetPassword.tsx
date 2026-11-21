import { Link, useForm } from '@inertiajs/react';
import PublicLayout from '../components/PublicLayout';
import { Errors } from '../components/UI';

export default function ResetPassword({ id, token }: { id: string; token: string }) {
  const form = useForm({ id, token, password: '', password_confirmation: '' });
  return (
    <PublicLayout title="Elige tu contraseña">
      <main id="main" className="login-main">
        <section className="login-copy">
          <h1>Elige tu contraseña.</h1>
          <p>Tu cuenta conserva las reservas y el historial del portal y del bot.</p>
        </section>
        <section className="login-panel">
          <h2>Nueva contraseña</h2>
          <Errors />
          <form
            onSubmit={(e) => {
              e.preventDefault();
              form.post('/recuperar/confirmar', {
                onFinish: () => form.reset('password', 'password_confirmation'),
              });
            }}
          >
            <div className="field">
              <label htmlFor="new-password">Contraseña</label>
              <input
                id="new-password"
                type="password"
                autoComplete="new-password"
                required
                minLength={8}
                maxLength={72}
                value={form.data.password}
                onChange={(e) => form.setData('password', e.target.value)}
              />
            </div>
            <div className="field">
              <label htmlFor="confirm-password">Confirmar contraseña</label>
              <input
                id="confirm-password"
                type="password"
                autoComplete="new-password"
                required
                minLength={8}
                maxLength={72}
                value={form.data.password_confirmation}
                onChange={(e) => form.setData('password_confirmation', e.target.value)}
              />
            </div>
            <button className="button full" disabled={form.processing}>
              Guardar contraseña
            </button>
          </form>
          <p>
            <Link href="/recuperar" className="text-link">
              Solicitar un enlace nuevo
            </Link>
          </p>
        </section>
      </main>
    </PublicLayout>
  );
}
