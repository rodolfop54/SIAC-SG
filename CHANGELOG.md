# Historial de cambios

## Corte de Fase 4 del 13 de noviembre de 2026

- Portal web y bot público simulado con lógica compartida en Laravel y datos sintéticos persistidos en PostgreSQL.
- Reserva, consulta, reprogramación, cancelación, gestión de agenda, auditoría y reportes con exportación CSV.
- El bot admite identificador LAB/FICT y clave dentro de la conversación. El registro web opcional conserva el mismo perfil y sus citas.
- D01. Aviso de limitación visible cuando el acceso por documento se bloquea.
- D02. Menú y salida reconocidos durante la identificación.
- D03. Opciones activas vinculadas a la sesión actual y limpieza del campo después de enviar.
- Verificación documentada en laboratorio. 41 pruebas PHPUnit y 1.192 aserciones, 16 escenarios Chrome y cinco carreras aprobadas.
- Pendientes de validación. Encuesta, evaluación con participantes, instalación independiente, carga de esta versión y dictamen TRL.

Consulta [pruebas](docs/TEST_RESULTS.md), [trazabilidad](docs/TRACEABILITY.md) y [auditoría](docs/AUDIT_RF_2026-11-13.md) para conocer el alcance y los registros de cada comprobación.
