# Despliegue de demostración y alternativa con Compose

La demostración académica está publicada en [Render Free](https://siac-sg.onrender.com), con [bot público](https://siac-sg.onrender.com/bot), HTTPS y PostgreSQL 18 compartido entre ambos canales. La imagen Docker se construyó en Render y se ejecutaron dos recorridos Chrome sobre el servicio real. Configuración, resultados y vencimiento de la base gratuita: [RENDER.md](RENDER.md). Esto no acredita aptitud para atención clínica real.

El acceso HTTPS temporal al servidor local mediante [Cloudflare](CLOUDFLARE.md) permanece como alternativa. Docker y Compose no están instalados en el equipo de implementación: **la variante Compose local sigue sin ejecutarse**, aunque la etapa Docker específica de Render ya fue construida y publicada.

## Alternativa Compose pendiente de ejecución

`Dockerfile` tiene etapas de assets Node, aplicación PHP-FPM y web Nginx. Compose conecta Nginx, PHP-FPM y PostgreSQL 18 por red interna. Solo publica el puerto web en `127.0.0.1:8080`; PostgreSQL no se publica. El volumen nombrado conserva la base. Los assets compilados se copian a Nginx para servirlos sin pasar por PHP.

Prepara un `.env` específico, fuera de Git, con APP_KEY nueva, contraseña propia para PostgreSQL y contraseña distinta para las cuentas sintéticas. Conserva el aviso de laboratorio. La configuración dentro del contenedor establece APP_ENV=production, APP_DEBUG=false, DB_HOST=db y DB_PORT=5432.

```text
docker compose build
docker compose up -d
docker compose exec app php artisan migrate --seed --force
docker compose exec app php artisan optimize
```

Prueba primero en localhost:8080. Para publicarlo, define dominio, proxy HTTPS, APP_URL y SESSION_SECURE_COOKIE=true; verifica login, CSRF y redirecciones detrás de ese proxy. No reutilices `.runtime` ni contraseñas de esta demostración. La base conserva exclusivamente información sintética.

## Verificaciones en destino

Ejecuta la suite sobre una base de pruebas separada, prueba los dos roles, el ciclo completo de cita y la carrera de concurrencia. Repite rendimiento con diez sesiones/cinco minutos y anota PHP-FPM, workers, CPU/RAM y volumen de datos del destino. Esta implementación no infiere ese resultado a partir del servidor PHP local.

Conserva una copia de seguridad PostgreSQL antes de migrar una versión posterior y conserva los locks. Tras cambiar configuración, reconstruye las cachés. No ejecutes borrados de volúmenes para actualizar el prototipo.
