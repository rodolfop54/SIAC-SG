import { Link, useForm } from '@inertiajs/react';
import { ArrowRight, ShieldCheck, MessageCircle } from 'lucide-react';
import PublicLayout from '../components/PublicLayout';
import { Errors, FieldError } from '../components/UI';
export default function Register({
  documentTypes,
  patientName,
}: {
  documentTypes: Record<string, string>;
  patientName: string | null;
}) {
  const form = useForm({
    name: patientName ?? '',
    document_type: 'CC',
    document_number: '',
    current_password: '',
    password: '',
    password_confirmation: '',
  });
  return (
    <PublicLayout title="Registro de paciente">
      <main id="main" className="registration-main">
        <section className="registration-copy">
          <ShieldCheck size={32} />
          <h1>
            Un solo perfil. <br />
            Todas tus citas.
          </h1>
          <p>
            Crea tu cuenta web con el mismo documento que utilizas en el bot. Tus reservas y tu
            historial seguirán relacionados.
          </p>
          <div className="notice">
            <MessageCircle size={20} />
            <span>Puedes reservar primero por el bot. Registrarte en la web es opcional.</span>
          </div>
          <Link href="/bot" className="text-link">
            Prefiero reservar por chat
            <ArrowRight size={15} />
          </Link>
        </section>
        <section className="login-panel registration-panel">
          <h2>Regístrate como paciente</h2>
          <p>Laboratorio académico: utiliza un nombre y documento ficticios.</p>
          <Errors />
          {patientName && (
            <div className="notice">
              Ya te identificaste en el bot como {patientName}. Escribe el mismo documento para
              vincular tus citas.
            </div>
          )}
          <form
            onSubmit={(e) => {
              e.preventDefault();
              form.post('/registro', {
                onFinish: () => form.reset('current_password', 'password', 'password_confirmation'),
              });
            }}
          >
            <div className="field">
              <label htmlFor="name">Nombres y apellidos de prueba</label>
              <input
                id="name"
                autoComplete="off"
                required
                maxLength={100}
                value={form.data.name}
                onChange={(e) => form.setData('name', e.target.value)}
                aria-invalid={!!form.errors.name}
              />
              <FieldError id="name-error" message={form.errors.name} />
            </div>
            <div className="field">
              <label htmlFor="document-type">Tipo de documento</label>
              <select
                id="document-type"
                value={form.data.document_type}
                onChange={(e) => form.setData('document_type', e.target.value)}
              >
                {Object.entries(documentTypes).map(([key, label]) => (
                  <option key={key} value={key}>
                    {label} ({key})
                  </option>
                ))}
              </select>
            </div>
            <div className="field">
              <label htmlFor="document-number">Número de documento ficticio</label>
              <input
                id="document-number"
                required
                maxLength={30}
                autoComplete="off"
                placeholder="Ejemplo: LAB000001"
                value={form.data.document_number}
                onChange={(e) => form.setData('document_number', e.target.value)}
                aria-invalid={!!form.errors.document_number}
              />
              <FieldError id="document-error" message={form.errors.document_number} />
              <span className="field-hint">
                Usa el prefijo LAB o FICT. No escribas documentos reales.
              </span>
            </div>
            <div className="field">
              <label htmlFor="current-password">
                {patientName
                  ? 'Clave del bot (opcional en esta sesión)'
                  : 'Clave del bot, si ya reservaste antes'}
              </label>
              <input
                id="current-password"
                type="password"
                autoComplete="current-password"
                maxLength={72}
                value={form.data.current_password}
                onChange={(e) => form.setData('current_password', e.target.value)}
              />
              <span className="field-hint">
                Solo se necesita para vincular un perfil que ya existe.
              </span>
              <FieldError id="current-error" message={form.errors.current_password} />
            </div>
            <div className="registration-passwords">
              <div className="field">
                <label htmlFor="new-password">Contraseña</label>
                <input
                  id="new-password"
                  type="password"
                  required
                  minLength={8}
                  maxLength={72}
                  autoComplete="new-password"
                  value={form.data.password}
                  onChange={(e) => form.setData('password', e.target.value)}
                />
                <FieldError id="password-error" message={form.errors.password} />
              </div>
              <div className="field">
                <label htmlFor="confirm-password">Confirmar contraseña</label>
                <input
                  id="confirm-password"
                  type="password"
                  required
                  minLength={8}
                  maxLength={72}
                  autoComplete="new-password"
                  value={form.data.password_confirmation}
                  onChange={(e) => form.setData('password_confirmation', e.target.value)}
                />
              </div>
            </div>
            <span className="field-hint">
              Al menos 8 caracteres. Esta contraseña funcionará en la web y en el bot.
            </span>
            <button className="button full" disabled={form.processing}>
              {form.processing ? 'Creando cuenta…' : 'Crear mi cuenta web'}
              <ArrowRight size={17} />
            </button>
            <p className="registration-login">
              ¿Ya tienes cuenta?{' '}
              <Link className="text-link" href="/acceso">
                Iniciar sesión
              </Link>
            </p>
          </form>
        </section>
      </main>
    </PublicLayout>
  );
}
