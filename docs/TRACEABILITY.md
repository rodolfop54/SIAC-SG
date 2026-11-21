# Trazabilidad

Fuente: objetivos específicos, tablas 5–7 y Anexo D del maestro de Fase 3. Las historias Jira se consultaron, sin modificar su estado.

Perfil del portal: RF01 incorpora edición del nombre y cambio verificado del correo mediante `ProfileController`/`ProfileEmailChange`; RF04 conserva paciente y citas; RF08 añade eventos de actualización sin datos personales; RF10 mantiene su entrada pública fuera del menú privado. Comprobaciones: `ProfileTest`, `profile.spec.ts` y las regresiones de sincronización existentes. Reglas: [PROFILE.md](PROFILE.md).

Incremento del 20 de noviembre: RF01/RF04/RF10 comparten cuenta y acceso directo desde el bot; `PatientEmailAccess`, `PatientEmailSender` y `PatientEmailAccessTest` añaden verificación por código, asociación protegida y recuperación por enlace. Brevo está activo en Render: se comprobó entrega y uso del código, alta sin contraseña y la misma reserva en el portal: [ACCESS_EMAIL.md](ACCESS_EMAIL.md). Se mantienen los identificadores T01–T19; las nuevas pruebas son regresiones técnicas adicionales.

Verificación adicional del despliegue: dos recorridos Chrome aprobados sobre [Render Free](RENDER.md) para RF01 (registro/acceso de paciente), RF02–RF06 y RF10, con reserva anónima y sincronización entre canales. La matriz conserva las comprobaciones locales completas; RF07–RF09 y la concurrencia no se reejecutaron sobre Render.

| Requisito | Historia | Implementación principal | Verificación |
| --- | --- | --- | --- |
| RF01 Acceso y roles | SCRUM-12 | AuthController, RoleMiddleware, IdleSession, Laravel session/CSRF | T01, T14, T15; regresión D01 en RequirementAuditTest y Chrome |
| RF02 Disponibilidad | SCRUM-13 | AvailabilityService, Slot::available, Availability.tsx | T02, T11–T13 |
| RF03 Reserva | SCRUM-14 | AppointmentService::book, índice SQL parcial, revisión/confirmación | T03, T16, carreras de procesos |
| RF04 Citas propias | SCRUM-15 | AppointmentController::index/show, AppointmentPolicy | T04, T14 |
| RF05 Reprogramación | SCRUM-16 | AppointmentService::reschedule, selección del mismo servicio | T05, T16 |
| RF06 Cancelación | SCRUM-17 | cancel, confirmación nativa y validación accepted | T06, teclado E2E |
| RF07 Agenda | SCRUM-18 | AgendaService, AdminController, Agenda.tsx | T07, E2E administrador |
| RF08 Auditoría | SCRUM-19 | AuditService, audit_events, Audit.tsx | T08, rollback T16; RequirementAuditTest verifica las seis operaciones |
| RF09 Reportes | SCRUM-21 | Agregados SQL por estado/servicio/fecha, Reports.tsx, CSV | T09, E2E administrador; límites de periodo en RequirementAuditTest |
| RF10 Bot público simulado y recordatorio | SCRUM-20 | Rutas públicas /bot; BotController, BotService, ReminderService, PatientIdentityService, RegistrationController, Bot.tsx; mismos AppointmentService/AvailabilityService y PostgreSQL que web | T10; PatientAccessTest; regresiones D02/D03 en RequirementAuditTest; BotSessionTest; patient-access.spec.ts y rf10-regressions.spec.ts (ciclo sin sesión web, sincronización bidireccional, sesiones, navegación y opciones antiguas) |

La decisión de RF10 es explícita: público sin inicio de sesión web previo, con identificación sintética y verificación por clave o código según la configuración. Una cuenta sirve directamente en bot y portal y conserva el mismo `users.id`. Los identificadores admiten tipos CC/TI/otros con número de laboratorio LAB/FICT. El documento no constituye autorización por sí solo. Reglas, límites y evidencia actual en [RF10_PUBLIC_BOT.md](RF10_PUBLIC_BOT.md); la auditoría original se conserva como registro histórico de los defectos.

| Requisito no funcional | Implementación/evidencia | Alcance pendiente |
| --- | --- | --- |
| RNF01 Responsividad | Reflujo/capturas automáticas a 360/768/1366; tablas en regiones desplazables | Dispositivos físicos y motores distintos de Chrome |
| RNF02 Comprensión | Etiquetas, errores españoles, revisión explícita y conservación de estado | Estudio de tiempos, éxito autónomo y satisfacción con participantes |
| RNF03 Accesibilidad | Axe en rutas principales, teclado en cancelación, focos y movimiento reducido | Lectores de pantalla y revisión humana exhaustiva; no certificación AA completa |
| RNF04 Autorización | Políticas y rol en servidor, pruebas negativas de todas las rutas de modificación | Evaluación externa de seguridad |
| RNF05 Sesiones/entradas | CSRF real comprobado, hash, inactividad 15min, validadores, escape React, límites a fallos | Configuración HTTPS/proxy del destino futuro |
| RNF06 Integridad | Índice parcial, transacciones, locks ordenados, rollback auditado y cinco carreras | Prueba del destino de despliegue |
| RNF07 Aislamiento | Identificador LAB/FICT obligatorio; pacientes y semilla sintéticos; sin SDK de Meta/Twilio; correo real solo al activar Brevo y verificar el destinatario | El formato no detecta nombres reales; comprobar entrega por correo y mantener uso exclusivo de laboratorio |
| RNF08 Rendimiento | Ensayo histórico de diez sesiones/cinco minutos, anterior al bot público; no repetido en este incremento | Medir la versión final y el destino; no extrapolar resultados históricos |
| RNF09 Reproducibilidad | Locks, entorno de ejemplo, migraciones, seeders, scripts y documentación | Instalación y ejecución por otro integrante en máquina limpia |

OE1 conserva fuentes y requisitos trazables, sin inventar resultados de la encuesta. OE2 se concreta en arquitectura/modelo/recorridos. OE3 corresponde a los módulos funcionales. OE4 tiene evidencia técnica automática y protocolo de uso; la evaluación humana prevista permanece pendiente. Implementar la aplicación no demuestra por sí solo TRL 4.
