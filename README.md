# SIAC-SG

Sistema de Información para la Administración de Citas. Prototipo funcional de laboratorio para la Fase 4 de Ingeniería de Sistemas, UNAD.

**Solo datos ficticios. No está conectado al Hospital de Sabanagrande ni a servicios sanitarios o de mensajería. Ninguna reserva es una cita médica real.**

Incluye acceso por roles, disponibilidad desde PostgreSQL, reserva con revisión y confirmación, historial propio, reprogramación, cancelación, gestión administrativa de franjas, auditoría, reportes filtrables con exportación CSV y bot guiado con recordatorios internos únicos.

## Guía de la versión académica

- [Historial de cambios](CHANGELOG.md)
- [Arquitectura y modelo de datos](docs/ARCHITECTURE.md)
- [Demostración por roles y canales](docs/DEMO.md)
- [Trazabilidad de requisitos](docs/TRACEABILITY.md)
- [Resultados de pruebas y límites](docs/TEST_RESULTS.md)
- [Preparación de despliegue](docs/DEPLOYMENT.md)

Para iniciar una copia, clona el repositorio y abre PowerShell en la carpeta clonada. Sigue «Ejecución local en Windows» para preparar dependencias, generar configuración local y arrancar la aplicación. Los archivos de `output/verification` y `output/playwright` documentan las ejecuciones del laboratorio.

## Bot público y pacientes

**Decisión de RF10 confirmada:** el bot es un canal principal público, sin inicio de sesión web previo. Usa identificadores sintéticos con prefijo **LAB** o **FICT**, por ejemplo `LAB000001`; no introduzcas documentos reales. La clave del perfil se exige para recuperar citas existentes. Menú/salida funcionan en cualquier paso y las opciones activas pertenecen a la sesión actual. Arquitectura, limitaciones y resultados de las correcciones D01–D03 en [RF10_PUBLIC_BOT.md](docs/RF10_PUBLIC_BOT.md).

Abre **http://127.0.0.1:8000/bot** sin iniciar sesión. El chat tiene aspecto WhatsApp y solicita tipo/número de documento de prueba, nombre completo y una clave. Puedes reservar sin una cuenta web. Documentos: CC, TI, RC, CE, PA, PPT y otros identificadores; «Otros tipos de documento» amplía la lista.

El registro web es opcional en **/registro**. Usa el mismo tipo y número de documento: se activa la cuenta sobre el perfil existente y conserva todas sus citas. Desde la sesión verificada del bot la vinculación es directa; desde otro dispositivo se pide la clave previa. La contraseña elegida al registrarte pasa a funcionar en ambos canales. **El documento solo no da acceso al historial.** El número se guarda cifrado, con un índice HMAC único, y no aparece en la transcripción del chat.

En **/acceso**, los pacientes entran con documento y contraseña. Para las cuentas sintéticas anteriores y el administrador, selecciona **Cuentas de demostración** y usa el alias de `.runtime/demo-access.txt`. Todos los datos siguen siendo ficticios; no se comprueban documentos contra la Registraduría ni se conecta WhatsApp real. «Público» significa acceso sin cuenta; el despliegue en Internet sigue siendo una tarea posterior.

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

`artisan test` y las carreras de concurrencia utilizan exclusivamente `siac_sg_test`. Las pruebas de navegador usan las cuentas sintéticas de la base local y dejan actividad demostrable en su historial; no deben ejecutarse sobre un despliegue compartido. Si no tienes Chrome, instala Chromium con Playwright y ajusta `channel` en `playwright.config.ts`.

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

## Entrega y despliegue posterior

Laravel 13.42, React 19, Inertia Laravel 3.7 / React 3.11, Tailwind 4 y PostgreSQL 18. Las versiones exactas instaladas se conservan en `composer.lock` y `package-lock.json`. PHP 8.4 cumple el mínimo de Laravel 13. [Requisitos oficiales](https://laravel.com/framework/docs/13.x/releases).

Se incluyen `Dockerfile`, `compose.yaml` y Nginx para preparar un despliegue posterior. **La configuración Docker no se ejecutó en este equipo porque Docker no está instalado; no se publicó ningún servidor.** Revisa [DEPLOYMENT.md](docs/DEPLOYMENT.md) antes de usarla.

Documentación: [arquitectura](docs/ARCHITECTURE.md), [trazabilidad](docs/TRACEABILITY.md), [estado](docs/IMPLEMENTATION_STATUS.md), [referencias visuales](docs/DESIGN_REFERENCES.md), [decisiones](docs/DECISIONS.md) y [demostración](docs/DEMO.md).
