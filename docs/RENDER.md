# SIAC-SG en Render Free

Despliegue publicado y comprobado el 13 de noviembre de 2026 (America/Bogota): [portal](https://siac-sg.onrender.com) y [bot público](https://siac-sg.onrender.com/bot). Funciona sin mantener encendido el equipo local. El repositorio público es `rodolfop54/SIAC-SG`, rama `main`. El Blueprint raíz `render.yaml` creó solo dos recursos gratuitos: el servicio web `siac-sg` y PostgreSQL 18 `siac-sg-db`, ambos en Oregon. No crea workers, cron, Redis, discos, réplicas ni planes de pago.

Actualización del 20 de noviembre: cuenta compartida entre bot y portal y acceso por código/recuperación con Brevo. **La integración está activa en Render y se comprobó la entrega real y el uso de un código en el bot publicado**, con la misma reserva visible al entrar directamente al portal. La API HTTPS funciona dentro de las restricciones de Render Free, sin SMTP. Remitente y clave se configuraron en Environment, preservando las variables existentes y el mismo APP_KEY. El Blueprint no contiene la clave ni se modifica para copiar secretos; instrucciones y alcance: [ACCESS_EMAIL.md](ACCESS_EMAIL.md).

Servicio `srv-dbrrvkm1egvs73c3a91g`, despliegue `dep-dbrrvku1egvs73c3a9qg` en estado `live`, commit de código `b278c97ea6e9197141ae1ec2195293de17af1cbc`. Base `dpg-dbrrvbm1egvs73c39e60-a`, estado `available`. Los dos recursos indican `plan: free`. La base vence el **13 de diciembre de 2026 a las 20:55, hora de Colombia** (`2026-12-14T01:55:59Z`). Metadatos sin credenciales: [registro](../output/verification/render-deployment.json).

## Arquitectura

Render construye la etapa final `render` del Dockerfile. Un solo contenedor ejecuta Nginx y PHP-FPM mediante Supervisor, con usuario sin privilegios, puerto `$PORT` y archivos temporales en `/tmp`. Los targets `app` y `web` de Compose se conservan. React se compila durante la construcción y se sirve desde Nginx; no necesita un servicio separado.

Al iniciar, el contenedor aplica migraciones y la semilla sintética idempotente, cachea configuración/rutas/vistas y arranca los procesos. Estas tareas van en el inicio porque el plan gratuito no ofrece pre-deploy commands ni shell/one-off jobs. La semilla conserva perfiles, citas y contraseñas existentes; no borra datos.

`DB_URL` se obtiene mediante `fromDatabase.connectionString`, por la red privada. Sesiones, límites y locks usan PostgreSQL (`SESSION_DRIVER=database`, `CACHE_STORE=database`), para conservar el estado al reiniciar o dormir la web. La base de Render es nueva: no se trasladan las citas ni secretos de la base local.

## Secretos y HTTPS

`APP_KEY` y `DEMO_PASSWORD` se generan en Render, sin incluir valores en GitHub. El inicio añade el prefijo `base64:` a la clave aleatoria de Render, conservando exactamente el mismo secreto entre reinicios. No regeneres APP_KEY después de guardar identificadores cifrados. El acceso administrativo usa `DEMO-ADMIN` y el valor privado de DEMO_PASSWORD en el panel de Render; los visitantes pueden crear su propio perfil ficticio en el bot.

El servicio define `APP_ENV=production`, `APP_DEBUG=false`, cookies Secure, sesiones cifradas y logs a stderr. `APP_URL` se obtiene de `RENDER_EXTERNAL_URL`. En Render se configura `TRUSTED_PROXIES=*` para reconocer esquema, puerto e IP de su proxy; no se confía en X-Forwarded-Host en ese modo. Fuera de Render permanece la confianza limitada a loopback, compatible con Cloudflare. Los archivos `.env`, runtimes y herramientas locales no entran en la imagen.

## Creación realizada y futuras instalaciones

1. Conectar Render al repositorio privado, autorizando únicamente los repositorios necesarios.
2. Abrir [el Blueprint de SIAC-SG](https://dashboard.render.com/blueprint/new?repo=https://github.com/rodolfop54/SIAC-SG).
3. Elegir `Rodolfo's workspace`, rama `main` y archivo `render.yaml`.
   Para el nombre del Blueprint se utilizó `SIAC-SG`.
4. Comprobar que **web y base indican Free** y aplicar el Blueprint.
5. Esperar a que la base esté disponible y el despliegue web termine en `live`; después probar `/`, `/bot`, `/registro`, acceso, reserva y relación de citas entre canales.

El repositorio y la rama se heredan del Blueprint vinculado. Los despliegues automáticos de la web quedan desactivados; no modificar en paralelo las variables gestionadas por el Blueprint sin actualizar la fuente correspondiente. Un cambio posterior de código puede publicarse manualmente en el servicio existente.

## Límites del plan gratuito

La web se suspende tras 15 minutos sin tráfico y puede tardar aproximadamente un minuto en despertar. Su filesystem es efímero. La base gratuita tiene 1 GB, vence a los 30 días y no dispone de backups gestionados. El plan tiene cuotas de compilación, tráfico y horas; no se interpreta como disponibilidad ilimitada. Antes del vencimiento debe decidirse cómo conservar o exportar los datos sintéticos, sin activar un plan de pago automáticamente.

## Verificación previa

- Blueprint aceptado por `render blueprints validate render.yaml`: [resultado](../output/verification/render-blueprint-validation.json).
- Suite de servidor: 43 pruebas y 1.196 aserciones aprobadas: [JUnit](../output/verification/render-predeploy-phpunit.xml).
- Sintaxis de `deploy/render/start.sh` comprobada con `bash -n`; formato PHP comprobado.
- Compilación TypeScript/Vite ejecutada correctamente antes de publicar los archivos de despliegue.
- Docker no está instalado en el equipo; la imagen se construyó correctamente en Render. Sus logs confirmaron migraciones, semilla, arranque de Nginx/PHP-FPM y health check `/up` con HTTP 200 antes de llegar a `live`.

## Verificación sobre el servicio publicado

Se ejecutaron **dos escenarios Chrome, ambos aprobados sin omitidos ni reintentos**, contra `https://siac-sg.onrender.com`, en 86,7 segundos. Crearon perfiles sintéticos nuevos, reservaron desde el bot sin sesión web, activaron el portal sobre la misma identidad y comprobaron consulta, recordatorios simulados, reprogramación, cancelación, salida y sincronización de cambios en ambos sentidos. Evidencia: [JSON de Chrome](../output/verification/render-public-e2e.json).

Comando ejecutado en PowerShell:

```powershell
$env:SIAC_BASE_URL='https://siac-sg.onrender.com'
npm.cmd run test:e2e -- tests/e2e/patient-access.spec.ts tests/e2e/rf10-regressions.spec.ts --grep 'Bot público: documento|RF10 público: ciclo completo' --timeout 180000
```

Inicio, bot, registro y `/up` respondieron 200; Mis citas sin sesión redirigió por HTTPS al acceso; `/.env` devolvió 403. La cookie de sesión lleva Secure y HttpOnly. Evidencias: [HTTP](../output/verification/render-http.json) y [atributos de cookie, sin sus valores](../output/verification/render-cookies.json).

Las 43 pruebas de servidor se ejecutaron contra la base local separada de pruebas, no contra la base compartida de Render. En Render se ejecutó únicamente la selección de dos recorridos públicos descrita; no se repitieron allí los 16 escenarios completos, los flujos administrativos, concurrencia ni carga. Los resultados históricos locales de RF01–RF10 conservan su alcance. No se ha medido rendimiento bajo tráfico concurrente en el plan gratuito. Los recorridos remotos dejan datos de laboratorio; no incluyen datos personales reales. No ejecutes la suite completa sobre Render sin preparar una base aislada y credenciales específicas.

Fuentes actuales: [plan gratuito](https://render.com/docs/free), [Blueprint](https://render.com/docs/blueprint-spec), [Laravel y Docker](https://render.com/docs/deploy-php-laravel-docker), [CLI](https://render.com/docs/cli).
