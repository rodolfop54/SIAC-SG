# SIAC-SG

Sistema de Información para la Administración de Citas. Prototipo funcional de laboratorio para la Fase 4 de Ingeniería de Sistemas, UNAD.

**Solo pacientes y documentos ficticios. No está conectado al Hospital de Sabanagrande ni a WhatsApp. Ninguna reserva es una cita médica real. El acceso por correo, cuando se active, envía mensajes reales al correo verificado del participante.**

**[Abrir la demostración de SIAC-SG en Render](https://siac-sg.onrender.com/)**. La primera carga puede tardar mientras se inicia el servicio.

Incluye acceso por roles, disponibilidad desde PostgreSQL, reserva con revisión y confirmación, historial propio, reprogramación, cancelación, gestión administrativa de franjas, auditoría, reportes filtrables con exportación CSV y bot guiado con recordatorios internos únicos.

## Bot público y pacientes

**Una cuenta para ambos canales:** el bot es público y no exige iniciar sesión previamente en la web. Usa identificadores sintéticos con prefijo **LAB** o **FICT**, por ejemplo `LAB000001`; no introduzcas documentos reales. Una vez verificada la identidad de acceso en el bot, **Entrar a mi espacio web** abre directamente las mismas citas, sin otro registro. Los perfiles anteriores también pueden entrar al portal con su documento y contraseña actuales.

Con el correo activado, la primera visita pide documento ficticio, nombre de prueba y correo: la cuenta se crea después de verificar un código. Al volver, el documento permite solicitar otro código al correo ya verificado. En la web se puede entrar con código o con contraseña; **Crear o recuperar contraseña** envía un enlace de un solo uso para elegirla. Nunca se envían contraseñas por correo. La cuenta, el historial y las reservas conservan el mismo `users.id`.

**Correo activado en Render:** el 20 de noviembre se comprobó una entrega real, la verificación del código en el bot y la misma reserva al entrar directamente al portal. `PATIENT_EMAIL_ACCESS` vale `false` por defecto para instalaciones nuevas: cada instalación debe configurar su propio remitente y clave privada antes de activarlo. Las cuentas anteriores conservan su contraseña. Para asociarles un correo se exige la clave actual o una sesión vigente de ese mismo paciente. **El documento solo no autoriza consultar ni modificar citas.** Número y correo se almacenan cifrados. Configuración gratuita, límites y evidencia: [ACCESS_EMAIL.md](docs/ACCESS_EMAIL.md).

En **/acceso**, los pacientes entran con documento y contraseña. Para las cuentas sintéticas anteriores y el administrador, selecciona **Cuentas de demostración** y usa el alias de `.runtime/demo-access.txt`. No se comprueban documentos contra la Registraduría. Hay una demostración de laboratorio en Render, enlazada arriba; el túnel descrito abajo es una alternativa local. Arquitectura y regresiones del bot: [RF10_PUBLIC_BOT.md](docs/RF10_PUBLIC_BOT.md).

## Perfil del paciente

El menú del portal contiene **Disponibilidad**, **Mis citas** y **Mi perfil**. El bot conserva su entrada pública independiente en `/bot`.

En `/perfil` se puede editar el nombre y cambiar el correo. La dirección actual sigue activa hasta verificar un código enviado al nuevo buzón. Si el acceso no se confirmó en los últimos cinco minutos, se pide la contraseña actual o un código al correo ya verificado. Confirmar un correo nuevo conserva paciente, documento y citas e invalida los accesos anteriores. El documento se muestra protegido y no se cambia desde este formulario. La opción de contraseña utiliza el enlace de recuperación existente. Reglas y comprobaciones: [PROFILE.md](docs/PROFILE.md).

## Compartir con personas en otras redes

Se preparó Cloudflare para compartir esta instalación local por un enlace HTTPS temporal:

```powershell
.\scripts\start-cloudflare.ps1
```

El comando muestra el enlace público y mantiene los procesos en segundo plano. Agrega `/bot` para entrar directamente al chat. El computador debe estar encendido y con Internet. Para cerrar el acceso:

```powershell
.\scripts\stop-cloudflare.ps1
```

El enlace cambia al reiniciar. Instrucciones, configuración y comprobaciones: [CLOUDFLARE.md](docs/CLOUDFLARE.md).

## Ejecución local en Windows

Requisitos: Node.js 24, npm, Git y conexión para descargar dependencias. El instalador prepara PHP portátil y PostgreSQL 18 dentro del proyecto, sin servicios globales. Necesita el runtime Visual C++ 2022 x64 utilizado por PHP.

Desde PowerShell, en la raíz:

```powershell
.\scripts\setup.ps1
.\scripts\start.ps1
```

Abre **http://127.0.0.1:8000**. Los alias sintéticos y la contraseña generada están en **`.runtime/demo-access.txt`**, excluido de Git. Usuario: `DEMO-ALBA`, `DEMO-LEO` o `DEMO-SOL`. Administrador: `DEMO-ADMIN`.

La primera instalación descarga PHP 8.4.27 con verificación SHA-256, instala las versiones de ambos archivos lock, genera `.env` y claves aleatorias, crea la base, ejecuta migraciones y datos sintéticos relativos al día de ejecución y compila la interfaz. Conserva un `.env` existente. No cambia la contraseña de cuentas ya sembradas.

El servidor web se detiene con Ctrl+C. Para detener PostgreSQL:

```powershell
npm.cmd run db:stop
```

Los datos permanecen en `.runtime/postgres`. En otra terminal puede iniciarse Vite con `npm.cmd run dev`; la compilación preparada no necesita Vite activo. Antes de cambiar configuración cacheada, usa `.runtime\php\php.exe artisan optimize:clear`.

## Instalación con runtimes existentes

PHP 8.4.1+ (se probó 8.4.27), Composer 2, Node 24 y PostgreSQL 18. Las dependencias Symfony resueltas en el lock requieren PHP 8.4.1 aunque Laravel 13 admite PHP 8.3. Extensiones PHP: PDO PostgreSQL, mbstring, OpenSSL, cURL, Fileinfo, Intl, ZIP y Sodium; PHPUnit requiere DOM/XML, incluidas en el PHP portátil empleado.

```text
composer install
npm ci
node scripts/prepare-env.mjs
php artisan key:generate
```

Configura `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` y `DB_PASSWORD` en `.env` para tu PostgreSQL. Conserva `DB_CONNECTION=pgsql`. La base debe existir; usa una base separada `siac_sg_test` para las pruebas. El usuario necesita permisos para crear tablas durante migraciones.

```text
php artisan migrate --seed
npm run build
php -S 127.0.0.1:8000 -t public scripts/router.php
```

Alternativamente, `npm run db:start` inicia PostgreSQL empaquetado para el entorno local y crea las bases de desarrollo y pruebas. Ese proceso permanece activo hasta detenerlo desde otra terminal con `npm run db:stop`.

## Comprobaciones

Con PostgreSQL local activo y Chrome instalado:

```powershell
.runtime\php\php.exe artisan optimize:clear
.runtime\php\php.exe artisan test
node scripts/concurrency.mjs
npm.cmd run typecheck
npm.cmd run build
npm.cmd run test:e2e
```

`artisan test` y las carreras de concurrencia utilizan exclusivamente `siac_sg_test`. La suite completa de navegador usa las cuentas sintéticas de la base local y deja actividad demostrable en su historial; no debe ejecutarse sobre un despliegue compartido sin aislamiento. La selección específica de dos escenarios públicos verificada en Render está documentada en [RENDER.md](docs/RENDER.md). Si no tienes Chrome, instala Chromium con Playwright y ajusta `channel` en `playwright.config.ts`.

Para la medición de cinco minutos con diez sesiones, sin reconstruir assets durante la ejecución:

```powershell
.runtime\php\php.exe artisan optimize
node scripts/performance.mjs
.runtime\php\php.exe artisan optimize:clear
```

Evidencias y limitaciones: [TEST_RESULTS.md](docs/TEST_RESULTS.md). El ensayo usa un segundo entre peticiones por sesión; no demuestra capacidad bajo cualquier carga. No equivale a validación con participantes.

## Recordatorios de laboratorio

En el bot, opción 5; o desde consola:

```text
php artisan siac:recordatorios
php artisan siac:recordatorios --at="2026-12-01T08:00:00-05:00"
```

El segundo ejemplo es un reloj explícito de prueba, no un dato de operación. Se seleccionan citas confirmadas dentro de las siguientes 24 horas. Un índice único impide repetir un recordatorio para la misma cita y franja. Se guardan únicamente en `simulated_messages`.

## Demostración publicada y despliegue

Laravel 13.42, React 19, Inertia Laravel 3.7 / React 3.11, Tailwind 4 y PostgreSQL 18. Las versiones exactas instaladas se conservan en `composer.lock` y `package-lock.json`. PHP 8.4 cumple el mínimo de Laravel 13. [Requisitos oficiales](https://laravel.com/framework/docs/13.x/releases).

La demostración está publicada en **[Render Free](https://siac-sg.onrender.com)**; el **[bot público](https://siac-sg.onrender.com/bot)** permite comenzar sin cuenta web. Usa exclusivamente datos sintéticos con prefijo LAB/FICT y una clave propia. Ambos canales comparten PostgreSQL en Render y funcionan sin mantener encendido el computador local. El contenedor fue construido en Render y dos recorridos Chrome comprobaron reserva, registro opcional y sincronización bidireccional.

La web gratuita se suspende tras 15 minutos sin tráfico; su primer acceso puede tardar alrededor de un minuto. La base gratuita vence el **13 de diciembre de 2026, hora de Colombia**. Detalles, evidencias y acceso administrativo: [RENDER.md](docs/RENDER.md). Las contraseñas locales no son las de Render. No se habilitaron recursos de pago.

Se conservan `compose.yaml` y los targets Docker previos para otra instalación. Compose no se ejecutó en este equipo porque Docker no está instalado. El [túnel Cloudflare](docs/CLOUDFLARE.md) es una alternativa temporal hacia la base local independiente. Revisa [DEPLOYMENT.md](docs/DEPLOYMENT.md) para distinguir ambas instalaciones.

Documentación: [arquitectura](docs/ARCHITECTURE.md), [trazabilidad](docs/TRACEABILITY.md), [estado](docs/IMPLEMENTATION_STATUS.md), [referencias visuales](docs/DESIGN_REFERENCES.md), [decisiones](docs/DECISIONS.md) y [demostración](docs/DEMO.md).
