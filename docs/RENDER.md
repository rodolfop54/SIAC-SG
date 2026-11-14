# SIAC-SG en Render Free

Configuración preparada para el repositorio privado `rodolfop54/SIAC-SG`, rama `main`. El Blueprint raíz `render.yaml` define solo dos recursos gratuitos: el servicio web `siac-sg` y PostgreSQL 18 `siac-sg-db`, ambos en Oregon. No crea workers, cron, Redis, discos, réplicas ni planes de pago.

## Arquitectura

Render construye la etapa final `render` del Dockerfile. Un solo contenedor ejecuta Nginx y PHP-FPM mediante Supervisor, con usuario sin privilegios, puerto `$PORT` y archivos temporales en `/tmp`. Los targets `app` y `web` de Compose se conservan. React se compila durante la construcción y se sirve desde Nginx; no necesita un servicio separado.

Al iniciar, el contenedor aplica migraciones y la semilla sintética idempotente, cachea configuración/rutas/vistas y arranca los procesos. Estas tareas van en el inicio porque el plan gratuito no ofrece pre-deploy commands ni shell/one-off jobs. La semilla conserva perfiles, citas y contraseñas existentes; no borra datos.

`DB_URL` se obtiene mediante `fromDatabase.connectionString`, por la red privada. Sesiones, límites y locks usan PostgreSQL (`SESSION_DRIVER=database`, `CACHE_STORE=database`), para conservar el estado al reiniciar o dormir la web. La base de Render es nueva: no se trasladan las citas ni secretos de la base local.

## Secretos y HTTPS

`APP_KEY` y `DEMO_PASSWORD` se generan en Render, sin incluir valores en GitHub. El inicio añade el prefijo `base64:` a la clave aleatoria de Render, conservando exactamente el mismo secreto entre reinicios. No regeneres APP_KEY después de guardar identificadores cifrados. El acceso administrativo usa `DEMO-ADMIN` y el valor privado de DEMO_PASSWORD en el panel de Render; los visitantes pueden crear su propio perfil ficticio en el bot.

El servicio define `APP_ENV=production`, `APP_DEBUG=false`, cookies Secure, sesiones cifradas y logs a stderr. `APP_URL` se obtiene de `RENDER_EXTERNAL_URL`. En Render se configura `TRUSTED_PROXIES=*` para reconocer esquema, puerto e IP de su proxy; no se confía en X-Forwarded-Host en ese modo. Fuera de Render permanece la confianza limitada a loopback, compatible con Cloudflare. Los archivos `.env`, runtimes y herramientas locales no entran en la imagen.

## Crear el Blueprint

1. Conectar Render al repositorio privado, autorizando únicamente los repositorios necesarios.
2. Abrir [el Blueprint de SIAC-SG](https://dashboard.render.com/blueprint/new?repo=https://github.com/rodolfop54/SIAC-SG).
3. Elegir `Rodolfo's workspace`, rama `main` y archivo `render.yaml`.
4. Comprobar que **web y base indican Free** y aplicar el Blueprint.
5. Esperar a que la base esté disponible y el despliegue web termine en `live`; después probar `/`, `/bot`, `/registro`, acceso, reserva y relación de citas entre canales.

El repositorio y la rama se heredan del Blueprint vinculado. Los despliegues automáticos de la web quedan desactivados; no modificar en paralelo las variables gestionadas por el Blueprint sin actualizar la fuente correspondiente. Un cambio posterior de código puede publicarse manualmente en el servicio existente.

## Límites del plan gratuito

La web se suspende tras 15 minutos sin tráfico y puede tardar aproximadamente un minuto en despertar. Su filesystem es efímero. La base gratuita tiene 1 GB, vence a los 30 días y no dispone de backups gestionados. El plan tiene cuotas de compilación, tráfico y horas; no se interpreta como disponibilidad ilimitada. Antes del vencimiento debe decidirse cómo conservar o exportar los datos sintéticos, sin activar un plan de pago automáticamente.

## Verificación previa

- Blueprint aceptado por `render blueprints validate render.yaml`: [resultado](../output/verification/render-blueprint-validation.json).
- Suite de servidor: 43 pruebas y 1.196 aserciones aprobadas: [JUnit](../output/verification/render-predeploy-phpunit.xml).
- Sintaxis de `deploy/render/start.sh` comprobada con `bash -n`; formato PHP comprobado.
- La compilación TypeScript/Vite se comprueba antes de publicar los archivos de despliegue.
- Docker no está instalado en el equipo; la construcción del contenedor y la verificación del servicio real deben completarse en Render. Una validación de YAML no acredita un despliegue funcional.

Fuentes actuales: [plan gratuito](https://render.com/docs/free), [Blueprint](https://render.com/docs/blueprint-spec), [Laravel y Docker](https://render.com/docs/deploy-php-laravel-docker), [CLI](https://render.com/docs/cli).
