# Estado de implementación

## Cuenta compartida y acceso por correo · 20 de noviembre de 2026

Bot y portal comparten acceso sin un segundo registro: las cuentas anteriores conservan citas y contraseña y el botón del bot abre directamente Mis citas. Se implementaron códigos de un solo uso, asociación protegida de correo a cuentas anteriores y recuperación mediante enlace para elegir contraseña, con vencimiento, límites de intentos y revocación de sesiones. **Brevo activado en Render por HTTPS:** remitente verificado, clave privada restringida y entrega real confirmada. El bot publicado verificó un código real, creó el paciente ficticio sin contraseña y abrió la misma reserva en el portal. Nuevas instalaciones conservan el correo desactivado por defecto hasta configurar su proveedor. Resultados verificables y límites del cambio de contraseña real en [TEST_RESULTS.md](TEST_RESULTS.md); instrucciones en [ACCESS_EMAIL.md](ACCESS_EMAIL.md). Las evaluaciones con participantes y TRL conservan sus pendientes.

## Demostración publicada en Render Free · 13 de noviembre de 2026

[Portal](https://siac-sg.onrender.com) y [bot público sin sesión web](https://siac-sg.onrender.com/bot), con un servicio Docker y PostgreSQL 18, ambos `free` y en Oregon. El despliegue llegó a `live`; migraciones, semilla y arranque funcionaron. Se repitieron **43 pruebas de servidor / 1.196 aserciones en la base local de pruebas** y **dos recorridos Chrome sobre Render**, todos aprobados. Estos últimos comprueban reserva anónima, registro sobre la misma identidad y sincronización bidireccional de reprogramación/cancelación; no sustituyen la suite local completa de 16 escenarios ni acreditan flujos administrativos o carga en el destino. Evidencias y límites: [RENDER.md](RENDER.md).

La base de Render es nueva e independiente de la local; portal y bot publicados sí comparten la misma base entre ellos. La web puede dormir a los 15 minutos y la base gratuita vence el 13 de diciembre de 2026, hora de Colombia. Se mantiene el alcance de laboratorio y datos sintéticos.

## Acceso remoto temporal con Cloudflare

Se prepararon y ejecutaron scripts para iniciar/detener un túnel HTTPS hacia un servidor PHP local separado, con depuración desactivada y cookies seguras. Portal y bot pueden abrirse desde otras redes mientras el equipo y el túnel estén activos. El servidor local habitual y `.env` se conservan. Se repitió la suite de servidor: **43 pruebas / 1.196 aserciones aprobadas**, incluyendo reconocimiento de HTTPS desde el proxy local y rechazo de cabeceras desde un origen no confiable. La prueba Chrome por el enlace público comprobó reserva anónima y activación de la misma cuenta/cita en web. Configuración, ejecución y alcance: [CLOUDFLARE.md](CLOUDFLARE.md). Esto no representa un despliegue de producción ni una nueva prueba de carga.

## Cierre verificado posterior a la auditoría · 13 de noviembre de 2026

**RF01–RF10 verificados en el alcance del prototipo de laboratorio. D01, D02 y D03 cerrados.** El bot público continúa accesible sin inicio de sesión web en `/bot`, con identificación sintética y clave dentro del chat. Portal y bot comparten las mismas tablas PostgreSQL y servicios Laravel. No se reconstruyó la aplicación ni se cambió su identidad visual.

Resultado final ejecutado: **41/41 pruebas de servidor, 1.192 aserciones; 16/16 escenarios Chrome en una ejecución conjunta; 5/5 carreras de concurrencia**. TypeScript, compilación Vite, Pint y Prettier correctos. Evidencias y límites en [RF10_PUBLIC_BOT.md](RF10_PUBLIC_BOT.md) y [TEST_RESULTS.md](TEST_RESULTS.md).

| Requisito | Estado verificado | Evidencia actual |
| --- | --- | --- |
| RF01 Acceso y roles | Completo en laboratorio | T01/T14/T15, PatientAccessTest y regresión D01 de servidor/Chrome; aviso de bloqueo visible en documento |
| RF02 Disponibilidad | Completo en laboratorio | T02, vacío en RequirementAuditTest y selección de horarios en Chrome |
| RF03 Reserva | Completo en laboratorio | T03/T16, persistencia tras recarga, confirmación pública y cinco carreras web/bot |
| RF04 Citas propias | Completo en laboratorio | T04/T14, confirmación/historial Chrome y vinculación de identidad entre canales |
| RF05 Reprogramación | Completo en laboratorio | T05, T10 y cambio en ambos canales con sincronización Chrome |
| RF06 Cancelación | Completo en laboratorio | T06, T10, diálogo/teclado y cancelación bidireccional Chrome |
| RF07 Agenda | Completo en laboratorio | T07/T14 y creación/bloqueo/habilitación administrativa en Chrome |
| RF08 Auditoría | Completo en laboratorio | T08/T16, seis operaciones y filtro en RequirementAuditTest, pantalla administrativa Chrome |
| RF09 Reportes | Completo en laboratorio | T09, periodo/servicio/estado/límites de día en RequirementAuditTest y pantalla administrativa Chrome |
| RF10 Bot público simulado | Completo en laboratorio | T10; PatientAccessTest; BotSessionTest; regresiones D02/D03; ciclo sin sesión web y sincronización en rf10-regressions.spec.ts |

No hay defectos funcionales abiertos reproducidos en este alcance tras la ejecución final. Permanecen pendientes la validación con participantes, lectores de pantalla/dispositivos físicos, instalación independiente, nuevas combinaciones de carreras y rollback y medición de carga de esta versión. El despliegue de demostración en Render se completó posteriormente, con el alcance de verificación descrito al principio. No se declara identidad civil verificada ni aptitud para pacientes reales. Los recordatorios son a demanda y no hay tareas periódicas programadas.

## Auditoría previa de ejecución · 13 de noviembre de 2026

La revisión inicial clasificó **8 RF completos y 2 parciales (RF01 y RF10)**. Se repitieron las 25 pruebas de servidor, los 12 recorridos Chrome y las cinco carreras de concurrencia. Siete comprobaciones exploratorias adicionales produjeron cuatro aprobaciones y **tres fallos funcionales reproducidos** que la suite anterior no cubría. El hallazgo histórico se conserva en [AUDIT_RF_2026-11-13.md](AUDIT_RF_2026-11-13.md); su cierre posterior figura al principio de este documento.

## Actualización: bot público, identidad y registro opcional

Por solicitud posterior del usuario, el bot ahora está fuera del layout privado, en `/bot`, accesible sin sesión web y con estilo WhatsApp. Solicita nombre y tipo/número de documento ficticio, junto con una clave de acceso. `/registro` permite activar después la web conservando el mismo perfil y todas sus citas. Se admiten CC, TI, RC, CE, PA, PPT y otros tipos; no hay conexión con validadores oficiales ni WhatsApp real.

Se aplicó una migración aditiva sin borrar datos existentes. Número cifrado e índice HMAC único; el documento solo no permite consultar o vincular un perfil. Las cuentas anteriores y administradores conservan sus alias en «Cuentas de demostración».

Verificación de esta ampliación: 25 pruebas PHPUnit y 322 aserciones aprobadas; la suite completa Chrome aprobó 12 escenarios. Tras mejorar la primera vista del chat, se repitieron los cinco escenarios públicos/registro correspondientes. Resultados específicos en TEST_RESULTS y `output/verification/phpunit-patient-access.xml`. La evaluación de rendimiento anterior corresponde a las rutas privadas previas, no mide esta nueva identificación pública.

Prototipo funcional local, construido directamente en el repositorio. Solo información ficticia. En esta entrega inicial no había acceso externo; posteriormente se habilitó el túnel temporal indicado al principio de este documento.

| Incremento | Resultado implementado | Verificación realizada |
| --- | --- | --- |
| 1 · Base funcional | Laravel/React/Inertia/Tailwind, PostgreSQL real, migraciones, semilla relativa, acceso por rol, disponibilidad, revisión y reserva persistente | Migraciones y seed ejecutados; pruebas HTTP/SQL; compilación y recorrido Chrome con recarga |
| 2 · Gestión completa | Citas propias, reprogramación, cancelación idempotente, gestión de agenda y auditoría | Conflictos y pertenencia en pruebas; rollback forzado; cinco carreras concurrentes; teclado y flujos administrativos en navegador |
| 3 · Integraciones académicas | Bot con operaciones compartidas, recordatorios internos únicos, reportes dinámicos y CSV | Ciclo completo del bot y coincidencia con web; conteos SQL; revisión móvil y Axe de rutas principales |
| 4 · Calidad | Tests, cachés/OPcache local, errores recuperables, tokens, diseño revisado, documentación y despliegue preparado | Resultados detallados en TEST_RESULTS; detector mecánico sin hallazgos; revisión independiente de Impeccable y corrección de opciones del bot |

Las comprobaciones visuales se agruparon al disponer de cada recorrido funcional y se repitieron tras los hallazgos. Los escenarios fallidos iniciales se conservaron como registros separados; no se contabilizan como aprobados por el hecho de haber implementado una corrección.

## Límites y pendientes

- Mobbin: consulta intentada y rechazada por requisito de plan de pago. No se usaron pantallas inaccesibles como referencias.
- Figma y Jira: accesibles y consultados. No se editaron archivos de diseño ni historias Jira.
- Validación con diez participantes, satisfacción, éxito autónomo y tiempos de tarea: pendiente de ejecución por el equipo académico.
- RNF09: pendiente instalación independiente por otro integrante. La ejecución local y los archivos lock no sustituyen esa comprobación.
- Despliegue: Docker/Nginx publicados en Render con HTTPS y dos recorridos públicos aprobados. Compose local sigue sin ejecutarse. Pendientes en el destino: suite administrativa, concurrencia, carga y gestión del vencimiento de PostgreSQL Free.
- Accesibilidad: evidencia automática y teclado en alcance definido; no se declara conformidad total WCAG 2.2 AA ni se probaron dispositivos físicos/lectores de pantalla.
- Rendimiento: se informa exclusivamente el ensayo y escenario medidos en TEST_RESULTS, incluidas limitaciones del servidor PHP local.

Los flujos RF01–RF10 tienen implementación persistente. Los pendientes anteriores corresponden a evidencia humana, acceso externo y operación en otro entorno.

## Cierre técnico histórico de la primera implementación

18 pruebas PHPUnit / 168 aserciones aprobadas; siete escenarios principales Chrome y un escenario público adicional aprobados; cinco carreras independientes de concurrencia aprobadas. TypeScript y build de producción correctos. Diez sesiones por cinco minutos: 1.186 lecturas, cero errores, p95 1,91s con un segundo entre peticiones por sesión, OPcache y cachés activas. Revisión independiente de Impeccable: hallazgo del bot resuelto y veredicto limitado a esa corrección. Ver TEST_RESULTS y VISUAL_REVIEW para el alcance concreto.
