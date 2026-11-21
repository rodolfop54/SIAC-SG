# Registro de pruebas técnicas

## Cuenta compartida y correo · 20 de noviembre de 2026

**54 pruebas de servidor aprobadas / 1.357 aserciones; 18 escenarios Chrome aprobados en una ejecución secuencial, sin omisiones ni reintentos, en 4,0 minutos.** TypeScript y compilación Vite correctos; formato Pint/Prettier comprobado. PostgreSQL de pruebas y base de demostración aislada, sin alterar las citas de la instalación original. Resumen reproducible sin credenciales ni rutas personales: [registro de esta ejecución](evidence/access-unified-2026-11-20.json).

Las once regresiones nuevas de servidor comprueban alta después de verificar correo, persistencia de la reserva al entrar desde el bot al portal, regreso al mismo perfil con código, vencimiento y uso único, cinco intentos, asociación protegida a cuentas anteriores, destinatario verificado inalterable, recuperación con revocación de sesiones y códigos antiguos, fallos del proveedor, respuesta genérica para documentos desconocidos y exclusión de secretos de formularios y cabeceras de recuperación. Las pruebas de navegador mantienen reservas/cambios/cancelaciones entre canales, administración, CSRF y sesiones; añaden las cuatro pantallas de correo/recuperación a 360, 768 y 1366 px con capturas, reflujo y Axe.

**Alcance del correo: API de Brevo interceptada mediante pruebas, sin envíos externos reales.** Falta crear/verificar el remitente, configurar la clave en Render y comprobar recepción y recorrido completo con el proveedor activo. `PATIENT_EMAIL_ACCESS=false` conserva el acceso con clave mientras tanto. No se repitieron concurrencia ni carga para este incremento. Las comprobaciones automáticas no sustituyen la evaluación de usabilidad con diez participantes ni una validación TRL en entorno relevante. Configuración: [ACCESS_EMAIL.md](ACCESS_EMAIL.md). Los registros anteriores conservan su alcance y fechas.

## Despliegue Render Free · 13 de noviembre de 2026

Commit de aplicación `b278c97ea6e9197141ae1ec2195293de17af1cbc`, servicio `https://siac-sg.onrender.com`, despliegue `live`. Construcción real Docker, migraciones, semilla, Nginx/PHP-FPM y `/up` correctos en Render. Web y PostgreSQL confirmados en plan gratuito.

| Comprobación ejecutada | Resultado y alcance | Evidencia |
| --- | --- | --- |
| Suite de servidor antes de publicar | 43 aprobadas / 1.196 aserciones; PostgreSQL local de pruebas | [JUnit](../output/verification/render-predeploy-phpunit.xml) |
| Compilación y configuración | TypeScript/Vite correctos; `bash -n` correcto; Blueprint válido; imagen construida en Render | [Validación](../output/verification/render-blueprint-validation.json), [despliegue](../output/verification/render-deployment.json) |
| Chrome contra Render | Dos aprobados, cero fallos/omitidos/reintentos, 86,7s: bot anónimo, reserva, registro vinculado, consulta/recordatorios, reprogramación/cancelación en ambos sentidos y salida | [JSON](../output/verification/render-public-e2e.json) |
| HTTPS y acceso público | Inicio/bot/registro/up 200; Mis citas redirige por HTTPS; `.env` 403; cookie Secure/HttpOnly | [HTTP](../output/verification/render-http.json), [cookie sin valores](../output/verification/render-cookies.json) |

Las pruebas remotas crearon únicamente perfiles y citas de laboratorio. No se ejecutaron contra Render la suite completa de 16 escenarios, flujos administrativos, concurrencia ni carga. Los resultados locales históricos conservan su alcance. Configuración, credenciales administrativas y límites gratuitos: [RENDER.md](RENDER.md).

## Acceso remoto temporal por Cloudflare

Se comprobó el túnel HTTPS real hacia el equipo local. **43 pruebas de servidor / 1.196 aserciones aprobadas** tras agregar dos regresiones de proxy: reconocimiento de HTTPS desde loopback y rechazo de cabeceras reenviadas desde un origen no confiable. Se ejecutó un escenario Chrome de reserva pública y activación web sobre el primer enlace y se repitió sobre el enlace final después de probar apagado/reinicio; **una prueba aprobada en cada ejecución**. El enlace final también respondió 200 en inicio/bot/registro, redirección HTTPS en Mis citas, 404 en `/.env` y cookie de sesión Secure/HttpOnly. No se repitió la suite completa de 16 escenarios ni carga por Internet. Resultados: [servidor](../output/verification/cloudflare-phpunit.xml), [Chrome final](../output/verification/cloudflare-public-e2e.json), [HTTP](../output/verification/cloudflare-http.json). Operación y límites en [CLOUDFLARE.md](CLOUDFLARE.md).

## Cierre D01–D03 y bot público · 13 de noviembre de 2026

Versión final comprobada mediante ejecución real, después de corregir los defectos de la auditoría:

| Comprobación | Resultado | Evidencia |
| --- | --- | --- |
| Servidor, PostgreSQL real | **41 aprobadas, 1.192 aserciones, cero fallos** | [JUnit](../output/verification/rf10-fixes-phpunit.xml) |
| Chrome, suite completa conjunta | **16 aprobados, cero fallos ni omitidos**, aproximadamente 4,1 minutos | [JSON final](../output/verification/rf10-fixes-e2e.json) |
| Concurrencia, procesos PHP independientes | **Cinco carreras aprobadas**: una confirmación, un conflicto y un evento de auditoría por franja | [JSON](../output/verification/rf10-fixes-concurrency.json) |
| TypeScript y Vite | Compilación correcta con `npm.cmd run build` | [Huellas de fuentes y manifiesto probado](../output/verification/rf10-final-source-hashes.json) |
| Formato | Pint y Prettier correctos sobre fuentes y pruebas modificadas | Comandos ejecutados; sin log independiente |
| Rutas públicas | GET/POST de bot sin middleware auth; CSRF de web conservado | [Rutas registradas](../output/verification/rf10-public-routes.json) |
| Comprobación local de formato | Cero identificadores fuera del prefijo LAB/FICT en el momento del chequeo; sesión cifrada activa | [Resultado sin números ni claves](../output/verification/rf10-local-data-check.json); no certifica que nombres sean ficticios |

Las siete exploratorias de la auditoría están integradas en `tests/Feature/RequirementAuditTest.php`, con D03 adaptado a su nuevo contrato de estado y opciones explícitas. `BotSessionTest.php` agrega nueve pruebas: 28 combinaciones de paso/comando, salida con sesión web vigente, otra conversación/recordatorios, acciones antiguas, entrada inválida con confirmación pendiente, formato sintético, expiración, límite de claves y cambio de actor. Las pruebas anteriores permanecen activas.

`tests/e2e/rf10-regressions.spec.ts` agrega cuatro escenarios. Comprueba el bloqueo visible D01, comandos y rechazo de documentos numéricos D02, ciclo completo del bot sin autenticación web y sincronización en ambos sentidos, y D03 con contextos independientes, dos pestañas, acción antigua y navegación Atrás. La reserva pública aparece al activar la cuenta web sobre el mismo perfil; reprogramar/cancelar por web se refleja en otro contexto del bot, y cancelar por bot se refleja en web. Los 12 recorridos anteriores también pasan, incluyendo Axe/reflujo en 360/768/1366 y teclado de cancelación. Se inspeccionó la captura móvil del bot: identidad visual conservada.

La primera pasada conjunta obtuvo 15/16: el caso nuevo de pestañas necesitaba esperar el menú y la navegación antes de comprobar sus resultados. Las repeticiones también detectaron una limpieza defectuosa del compositor: Inertia adopta los envíos correctos como valores por defecto y `reset()` podía restaurar una entrada anterior. Se corrigió con una fábrica de valores vacíos y bloqueo del campo durante el envío; la prueba comprueba ahora que se vacía tras cada mensaje. Se corrigió además una importación faltante de una prueba nueva. Se conserva el [primer resultado conjunto](../output/verification/rf10-first-pass-e2e.json); el resultado válido de cierre es el JSON final de 16 aprobados, posterior a todas las correcciones. La [repetición D03 aislada](../output/verification/rf10-d03-e2e.json) también aprobó antes de la ejecución conjunta final.

PHPUnit y concurrencia utilizan `siac_sg_test`, sin ejecutarse simultáneamente. Chrome utiliza exclusivamente la base local sintética de demostración y deja perfiles, citas y franjas de prueba. No se borró la información previa. No se repitió la prueba histórica de carga ni se ejecutaron Docker, una instalación limpia independiente, lectores de pantalla o evaluación con participantes. Las cinco carreras compiten por una reserva inicial; no cubren todas las combinaciones de reprogramación/cancelación/bloqueo.

Mecanismos y límites de identificación: [RF10_PUBLIC_BOT.md](RF10_PUBLIC_BOT.md). Estado actual RF01–RF10: [IMPLEMENTATION_STATUS.md](IMPLEMENTATION_STATUS.md). Los registros siguientes son históricos.

## Reejecución y exploración · 13 de noviembre de 2026

Suite existente: **25/25 pruebas de servidor, 322 aserciones; 12/12 Chrome; 5/5 carreras de concurrencia**. Exploratorias nuevas: **4 aprobadas y 3 fallidas, 125 aserciones ejecutadas**. Los fallos afectan el aviso de limitación del acceso por documento y dos casos de recuperación/sesión del bot. No se han corregido en esta auditoría. Evidencias y clasificación actual en [AUDIT_RF_2026-11-13.md](AUDIT_RF_2026-11-13.md); fuentes y resultados bajo `output/verification/audit-2026-11-13/`. Los resultados históricos siguientes no sustituyen esta revisión.

## Ampliación posterior: bot público y pacientes

La suite de servidor amplió la cobertura a **25 pruebas / 322 aserciones, todas aprobadas**, incluyendo visitante anónimo, creación de perfil con documento cifrado, reserva sin cuenta web y posterior activación sobre el mismo ID. Comprueba también que documento sin clave no permite ver ni vincular un perfil, que el cambio de formato del número no crea duplicados, que distintos tipos conservan su identidad y que la sesión anónima expirada no confirma reservas. Evidencia: `output/verification/phpunit-patient-access.xml`.

La suite Chrome completa aprobó **12 escenarios**: los flujos previos, reserva anónima, registro opcional, acceso posterior con documento, bot estilo WhatsApp y registro accesibles a 360/768/1366. Tras el refinamiento visual de la identificación y el espaciado móvil se repitieron los cinco escenarios públicos/registro afectados. El número y la clave no aparecen en la conversación. Capturas: `whatsapp-bot-{360,768,1366}.png`, `registration-{360,768,1366}.png` y `public-bot-booking.png`, bajo `output/playwright`.

El primer ensayo de la ampliación detectó que los límites genéricos de chat y registro compartían una clave por IP: diez mensajes podían bloquear el registro. Se separaron los prefijos de límite y se repitieron las pruebas con éxito. Los datos de rendimiento de la versión anterior se conservan como evidencia histórica, no como medición de las nuevas rutas públicas.

Fecha de trabajo: 12 de noviembre de 2026 en America/Bogota. Los JSON usan UTC, que puede indicar el día 13. Entorno: Windows, PHP 8.4.27, Laravel 13.42, PostgreSQL 18.6, Node 24.21 y Chrome. Detalles verificables en `output/verification/environment.json`.

Precondiciones: PostgreSQL local activo, migraciones aplicadas, `.env` generado sin datos reales y base independiente `siac_sg_test`. PHPUnit usa transacciones de prueba; el ensayo de carreras crea y limpia únicamente sus propias filas en esa base. E2E trabaja con datos sintéticos de la base local.

## Casos T01–T19

| Caso | Pasos / propósito | Resultado observado y evidencia |
| --- | --- | --- |
| T01 | Acceso inválido/válido, hash y salida | HTTP PHPUnit: rechazo, autenticación, hash y sesión finalizada comprobados |
| T02 | Filtros de servicio/fecha, exclusión de ocupado/bloqueado/pasado/servicio deshabilitado | Solo franja libre en respuesta Inertia; vacío visible en UI |
| T03 | Confirmar y repetir UUID, persistir y recargar | Una cita/auditoría por solicitud; navegador conserva código tras recarga |
| T04 | Consultar historial y detalle propio | Solo la reserva del actor en respuesta; detalle disponible |
| T05 | Mover a ocupado, comprobar original, liberar destino y mover | Conflicto conserva slot y código; cambio posterior correcto |
| T06 | Cancelar sin confirmar y confirmar dos veces | Rechazo inicial; un evento de cancelación; franja libre e historial conservado |
| T07 | Crear, evitar cruce, bloquear/habilitar e intentar bloquear ocupado | Creación y cambios correctos; ocupado conserva reserva; probado también en Chrome |
| T08 | Revisar auditoría de reserva/cancelación y canal | Actor, antes/después y canal correctos; sin contraseñas |
| T09 | Agrupar dos citas, una cancelada, por servicio y periodo | Totales SQL: 2, confirmadas 1, canceladas 1; reportes administrativos accesibles |
| T10 | Bot completo, opción inválida, recordatorio repetido y recuperación | Reserva/cambio/cancelación compartidos con web; recordatorio único y entradas inválidas sin mutación; revisión visual detectó opciones ocultas por recordatorio y se corrigió |
| T11 | Abrir disponibilidad/citas/bot/agenda/auditoría/reportes en 360/768/1366 | Sin overflow horizontal del documento; tablas desplazables dentro de región; capturas en output/playwright |
| T12 | Etiquetas, revisión antes de confirmar, conflicto y cancelación reversible | Flujos Chrome completos; entradas y selección preservadas ante errores. Metas de uso con participantes pendientes |
| T13 | Axe WCAG seleccionadas, foco inicial, Escape, retorno y movimiento reducido | Suite E2E verifica Axe en 18 combinaciones ruta/ancho, diálogo por teclado y reduced-motion. Gráfico desplazable corregido con foco. Alcance no certifica AA completa |
| T14 | Leer/modificar cita ajena y acceder a todas las rutas administrativas como usuario | Solicitudes rechazadas por servidor; el bot tampoco expone una cita ajena |
| T15 | Entradas manipuladas, inactividad, CSRF sin token y salida | Validación, rol intacto, expiración a 16min simulados y 419 real comprobados; límite de intentos fallidos implementado |
| T16 | Forzar fallo en auditoría, duplicar SQL activo y competir por franja | Rollback sin cita parcial, índice SQL rechaza duplicado; 5/5 carreras: una reserva, un conflicto y una auditoría |
| T17 | Revisar esquema, descartar entrada personal desconocida y ausencia de integraciones | Sin documento/teléfono; contenido libre inválido no se almacena. Revisión de código: sin clientes sanitarios, Meta o Twilio |
| T18 | Diez sesiones autenticadas durante cinco minutos | Ver sección de rendimiento y JSON; no se atribuye aprobación a ensayos de protocolo incorrecto |
| T19 | Instalar con locks, migraciones, semilla y pruebas desde instrucciones | Herramientas y recorrido local ejecutados. Pendiente réplica por otro integrante en máquina limpia |

## Ejecuciones y correcciones

La primera suite PHPUnit aprobó 17 pruebas/132 aserciones, incluyendo dos pruebas triviales del esqueleto Laravel. Se retiraron esas dos y se amplió la suite con regresiones del bot y acceso fallido. **La ejecución final aprobó 18 pruebas funcionales y 168 aserciones en 5,43s**. Evidencia JUnit: `output/verification/phpunit.xml`.

La primera ejecución E2E tuvo 3 aprobados/4 fallidos: el test del bot leía antes de acabar la petición; el límite de acceso contaba entradas válidas; el test de agenda no filtraba un registro situado en otra página. Se corrigieron la espera, el limitador y el filtro del escenario. Otra ejecución coincidió con carga del servidor de desarrollo y no se considera aprobación.

La auditoría posterior encontró una región de gráfico sin acceso de teclado. Después de corregirla, la suite completa de siete escenarios aprobó. La revisión visual independiente encontró que un recordatorio podía ocultar opciones guiadas del bot; se corrigió la selección del mensaje conversacional y el saludo cuando la transcripción solo contenía recordatorios, con regresiones adicionales. **La repetición final volvió a aprobar los siete escenarios, con las capturas renovadas, en 1,3 minutos.**

Registros iniciales: `output/verification/e2e-initial.json`, `e2e-load-interference.json`. Resultados de cierre: `test-results/e2e-results.json` y `output/verification` según los comandos finales. Las capturas son del prototipo funcionando, no imágenes de diseño.

La comprobación pública adicional aprobó un escenario separado con inicio, acceso y página 404 en los tres anchos (nueve combinaciones y auditorías Axe adicionales). Se conservan **siete escenarios principales aprobados** en `output/verification/e2e-final.json` y **un escenario público aprobado** en `output/verification/e2e-public-final.json`; no se presenta esa separación como una única ejecución. Hay 28 capturas PNG. Formato PHP de fuentes comprobado con Pint y Composer validado; los archivos generados de bootstrap/cache se excluyen del alcance de formato.

## Rendimiento

Ejecución válida de cierre: **1.186 solicitudes, diez sesiones, 302,693 segundos, cero errores, p95 1.913,46ms y máximo 3.288,32ms**. La meta p95 ≤2s se cumple en este escenario local de lectura con 1.000ms entre peticiones por sesión. CPU Intel i5-3470 3,20GHz, 16GB RAM, Windows 10.0.26200. No significa que todas las peticiones tardaran menos de dos segundos.

Script: `scripts/performance.mjs`. Diez contextos autenticados independientes; lectura de disponibilidad y citas, calentamiento previo, versión Inertia correcta, respuesta HTTP completa. Configuración de cierre: OPcache y cachés Laravel; cinco minutos, un segundo de lectura entre peticiones por sesión. Equipo y resultados quedan en `output/verification/performance.json`.

El primer ensayo de cinco minutos omitió `X-Inertia-Version`, recibió 409 de renovación de assets y no mide correctamente las rutas. Se conserva como `performance-initial.json`. Ensayos breves a 250ms entre peticiones mostraron p95 superior a dos segundos en el servidor PHP de un solo proceso; se conserva `performance-warmup.json`. La carga y el intervalo del ensayo final deben acompañar cualquier interpretación del resultado; no se afirma capacidad bajo tráfico ilimitado ni rendimiento del futuro PHP-FPM.

No se midieron tiempos de tareas humanas, satisfacción ni finalización autónoma. No se certifica TRL 4.
