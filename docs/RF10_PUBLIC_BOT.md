# RF10 · Bot público simulado integrado

El bot y el portal son dos canales principales de SIAC-SG. `/bot` es público y permite identificar un perfil de laboratorio y gestionar citas sin iniciar sesión en el portal. No usa WhatsApp, Meta, Twilio, correo ni mensajes externos. Se conserva la interfaz verde/blanca y el estilo de chat existente.

## Identificación y autorización

1. El visitante elige CC, TI u otro tipo y un identificador sintético con prefijo `LAB` o `FICT`, entre 6 y 20 caracteres alfanuméricos una vez normalizado. Ejemplo: `LAB000001`. Un número nacional numérico se rechaza antes de crear un perfil. Se solicitan exclusivamente nombres de prueba.
2. Un perfil nuevo pide nombre ficticio y clave confirmada (8–72 caracteres). Un perfil existente exige su clave antes de revelar nombre, historial o citas. El número por sí solo no autoriza ninguna operación. Cinco claves incorrectas por perfil/IP bloquean temporalmente la verificación durante un minuto.
3. La clave se guarda como hash. El número se cifra en PostgreSQL, se oculta de la serialización y se indexa mediante HMAC-SHA256 único sobre tipo/número normalizados. Tipo distinto supone perfil distinto. Los campos de identificación y claves se excluyen de la transcripción. Se conserva la sesión cifrada y CSRF Laravel; la identificación correcta regenera el ID de sesión.
4. El contexto del bot usa `bot_patient_id`, `bot_last_activity` y `bot_actor_id`, con inactividad máxima de 900 segundos. El actor procede del servidor, no de un ID de paciente enviado por el cliente. Políticas y servicios verifican pertenencia de la cita. En una sesión web de paciente vigente puede usarse ese actor sin repetir identificación; la entrada pública nunca depende de ello.
5. `menú`/`menu` reinician la identificación pendiente o vuelven al menú si ya hay identidad verificada. `0`/`salir` eliminan identidad, estado y opciones, ocultan el historial y muestran «Iniciar conversación». Estos comandos funcionan en todos los pasos. Salir del bot no cierra una sesión web vigente, pero obliga a identificarse de nuevo en el chat.

No hay prueba de identidad civil: el creador inicial de un identificador sintético elige su clave. El prefijo impide usar directamente un documento nacional numérico, pero no detecta nombres reales ni información personal escrita deliberadamente. La pantalla de clave también permite inferir que existe un identificador de laboratorio. No hay segundo factor, validación oficial ni recuperación de clave. Es protección de un laboratorio sintético, no un sistema apto para datos de pacientes reales. Una futura puesta en producción exigiría otro proceso de identidad y revisión de seguridad; no se implementa en este incremento.

## Integración y estado de conversación

Web y bot llaman a los mismos `AppointmentService` y `AvailabilityService`. Los dos operan sobre las mismas tablas PostgreSQL `users`, `services`, `slots`, `appointments`, `audit_events` y `simulated_messages`. No hay un almacén de reservas en React ni un servicio de reservas paralelo. Reserva, cambio y cancelación conservan las mismas transacciones, locks, índice parcial y reglas de pertenencia en ambos canales.

El registro `/registro` es opcional. Con el mismo tipo/número y una sesión del bot verificada vigente, activa la web sobre el mismo `users.id`. Desde otra sesión exige la clave previa. No duplica paciente ni citas ni permite asignarse rol administrador. La nueva contraseña funciona en ambos canales. No se modifica el nombre/documento del perfil al activarlo.

La transcripción persistida es un historial compartido del paciente; sus opciones antiguas son datos históricos. Las acciones activas vienen exclusivamente de `session.bot.prompt` y la prop `current`, asociadas al actor y al paso actuales. Una sesión sin contexto inicia un menú nuevo, aunque exista historial. Una conversación de otra sesión o un recordatorio no sustituye esas opciones. Si el último texto del historial pertenece a otra conversación, la interfaz añade al final el mensaje actual de esta sesión.

La interfaz envía `prompt_id` para rechazar acciones de páginas/pestañas antiguas sin mutar datos. Ese ID no es una credencial; si se omite en un consumidor HTTP de laboratorio, siguen aplicándose el estado de sesión, las políticas y todas las reglas de negocio. Volver a Bot desde el historial del navegador vuelve a consultar el estado del servidor. Salir/resetear elimina el historial de navegación de Inertia. Una entrada guiada desconocida descarta la confirmación pendiente y ofrece recuperación.

El campo del chat está bloqueado durante un envío y se limpia con valores iniciales siempre vacíos. Esto evita que la actualización automática de valores por defecto de Inertia restaure una entrada anterior al avanzar entre documento, nombre y clave. La regresión Chrome comprueba la limpieza en cada mensaje del recorrido.

Los recordatorios se guardan dentro del simulador y son únicos por cita/franja/inicio, para citas confirmadas dentro de las próximas 24 horas. Se consultan por el menú 5 o el comando `siac:recordatorios`; no hay emisión periódica programada.

## Correcciones de la auditoría

| Defecto | Cambio | Regresión integrada |
| --- | --- | --- |
| D01 | `AuthController` devuelve el bloqueo en `document_number` para pacientes y `alias` para demostración; el campo visible presenta el aviso. | `RequirementAuditTest`, escenario D01 en `rf10-regressions.spec.ts` |
| D02 | Comandos globales procesados antes del paso de identificación, incluidos campos de clave. Limpieza de datos pendientes al reiniciar/salir. | `RequirementAuditTest`, `BotSessionTest` (28 combinaciones de paso/comando), escenario D02 en Chrome |
| D03 | Menú inicial por sesión/actor; opciones desde el estado actual; rechazo de `prompt_id` antiguo y recarga al volver por historial. | `RequirementAuditTest`, `BotSessionTest`, escenario D03 con contextos y pestañas en Chrome |

## Verificación

Resultado final ejecutado el 13 de noviembre de 2026: **41 pruebas de servidor aprobadas / 1.192 aserciones; 16 escenarios Chrome aprobados en una misma ejecución; cinco carreras de concurrencia aprobadas**. Compilación TypeScript/Vite y formato correctos. Evidencias y repeticiones en [TEST_RESULTS.md](TEST_RESULTS.md); estado de los diez requisitos en [IMPLEMENTATION_STATUS.md](IMPLEMENTATION_STATUS.md). D01–D03 están cerrados en el alcance de laboratorio. Se preservan los informes de la auditoría anterior como evidencia histórica, no como estado final de este incremento.

Las pruebas nuevas cubren identificación pública, comandos, expiración, límite de claves, protección de perfil existente, documentos numéricos rechazados, opciones entre sesiones/recordatorios, acciones antiguas, ciclo de citas sin autenticación web y sincronización de cambios entre ambos canales. Las pruebas existentes siguen cubriendo los diez requisitos y la integridad SQL. Ningún resultado se considera aprobado solo por tener un archivo de prueba.

Para repetir con PostgreSQL y servidor locales activos:

```powershell
.\.runtime\php\php.exe artisan test
npm.cmd run build
npm.cmd run test:e2e
node scripts/concurrency.mjs
```

Quedan pendientes validación con participantes, revisión humana de accesibilidad, instalación independiente, carga actualizada y despliegue posterior. No se sustituye la identificación de laboratorio por autenticación civil segura ni se presenta como producción.
