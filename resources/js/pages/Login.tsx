import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, Eye, EyeOff, ShieldCheck, FlaskConical } from 'lucide-react';
import { useState } from 'react';
import { Logo } from '../components/Layout';
import { FieldError } from '../components/UI';
export default function Login({
  ended,
  documentTypes,
}: {
  ended: boolean;
  documentTypes: Record<string, string>;
}) {
  const [visible, setVisible] = useState(false);
  const [demo, setDemo] = useState(false);
  const form = useForm({ alias: '', document_type: 'CC', document_number: '', password: '' });
  return (
    <>
      <Head title="Acceso de prueba" />
      <div className="public-shell">
        <header className="public-header">
          <Link href="/">
            <Logo />
          </Link>
          <Link href="/" className="text-link">
            <ArrowLeft size={16} />
            Volver al inicio
          </Link>
        </header>
        <div className="simulation-banner">
          <FlaskConical size={15} />
          Datos ficticios · Sin conexión a servicios sanitarios reales
        </div>
        <main className="login-main">
          <section className="login-copy">
            <ShieldCheck size={34} />
            <h1>
              Bienvenido <br />a tu espacio.
            </h1>
            <p>Una cuenta de prueba. Todas tus citas organizadas.</p>
            <div className="login-context">
              <strong>Un laboratorio, dos recorridos</strong>
              <p>
                Como usuario, consulta y gestiona tus reservas. Como administrador, organiza la
                agenda y revisa la actividad.
              </p>
              <p>Usa el mismo documento de prueba de tu perfil del bot para conservar tus citas.</p>
              <Link href="/bot" className="text-link">
                Reservar sin cuenta web
                <ArrowRight size={15} />
              </Link>
            </div>
          </section>
          <section className="login-panel">
            <h2>Iniciar sesión</h2>
            <p>Accede con tu documento de prueba y tu contraseña.</p>
            <div className="login-modes">
              <button
                type="button"
                className={!demo ? 'selected' : ''}
                onClick={() => {
                  setDemo(false);
                  form.setData('alias', '');
                }}
              >
                Con documento
              </button>
              <button
                type="button"
                className={demo ? 'selected' : ''}
                onClick={() => {
                  setDemo(true);
                  form.setData('document_number', '');
                }}
              >
                Cuentas de demostración
              </button>
            </div>
            {ended && (
              <div role="status" className="notice">
                La sesión finalizó. Puedes volver a entrar con tu cuenta de prueba.
              </div>
            )}
            <form
              onSubmit={(e) => {
                e.preventDefault();
                form.post('/acceso', { onFinish: () => form.reset('password') });
              }}
            >
              {demo ? (
                <div className="field">
                  <label htmlFor="alias">Alias de prueba</label>
                  <input
                    id="alias"
                    autoComplete="username"
                    autoFocus
                    value={form.data.alias}
                    onChange={(e) => form.setData('alias', e.target.value)}
                    placeholder="Tu alias ficticio"
                    required
                    maxLength={40}
                    aria-invalid={!!form.errors.alias}
                    aria-describedby={form.errors.alias ? 'alias-error' : undefined}
                  />
                  <FieldError id="alias-error" message={form.errors.alias} />
                </div>
              ) : (
                <>
                  <div className="field">
                    <label htmlFor="login-document-type">Tipo de documento</label>
                    <select
                      id="login-document-type"
                      value={form.data.document_type}
                      onChange={(e) => form.setData('document_type', e.target.value)}
                    >
                      {Object.entries(documentTypes ?? {}).map(([key, label]) => (
                        <option key={key} value={key}>
                          {label} ({key})
                        </option>
                      ))}
                    </select>
                  </div>
                  <div className="field">
                    <label htmlFor="login-document-number">Número de documento ficticio</label>
                    <input
                      id="login-document-number"
                      required
                      maxLength={30}
                      autoComplete="username"
                      placeholder="Ejemplo: LAB000001"
                      value={form.data.document_number}
                      onChange={(e) => form.setData('document_number', e.target.value)}
                      aria-invalid={!!form.errors.document_number}
                      aria-describedby={form.errors.document_number ? 'document-error' : undefined}
                    />
                    <FieldError id="document-error" message={form.errors.document_number} />
                  </div>
                </>
              )}
              <div className="field">
                <label htmlFor="password">Contraseña de prueba</label>
                <div className="password-field">
                  <input
                    id="password"
                    type={visible ? 'text' : 'password'}
                    autoComplete="current-password"
                    value={form.data.password}
                    onChange={(e) => form.setData('password', e.target.value)}
                    required
                    maxLength={200}
                    aria-invalid={!!form.errors.password}
                  />
                  <button
                    type="button"
                    className="icon-button"
                    onClick={() => setVisible(!visible)}
                    aria-label={visible ? 'Ocultar contraseña' : 'Mostrar contraseña'}
                  >
                    {visible ? <EyeOff size={18} /> : <Eye size={18} />}
                  </button>
                </div>
                <FieldError id="password-error" message={form.errors.password} />
              </div>
              <button className="button full" disabled={form.processing}>
                {form.processing ? 'Verificando…' : 'Entrar a mi espacio'}
                <ArrowRight size={17} />
              </button>
            </form>
            <p className="registration-login">
              ¿Aún no tienes cuenta?{' '}
              <Link href="/registro" className="text-link">
                Registrarme
              </Link>
            </p>
            <div className="login-footnote">
              <ShieldCheck size={17} />
              <span>
                Acceso restringido a cuentas sintéticas. Tu sesión termina después de 15 minutos sin
                actividad.
              </span>
            </div>
          </section>
        </main>
        <footer className="public-footer">SIAC-SG · Laboratorio académico · UNAD</footer>
      </div>
    </>
  );
}
