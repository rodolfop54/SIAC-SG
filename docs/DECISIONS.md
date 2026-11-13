# Decisiones técnicas y visuales

Corrección posterior a la auditoría del 13 de noviembre: RF10 permanece público y es un canal principal junto al portal web. Se mantienen los mismos servicios Laravel y tablas PostgreSQL. El aviso de limitación se asocia al campo del modo de acceso; los comandos globales preceden a la identificación; las opciones activas proceden del estado de sesión y no del historial. Se añade prefijo LAB/FICT obligatorio para identificadores sintéticos. No se cambia la identidad visual. Mecanismo y limitaciones en [RF10_PUBLIC_BOT.md](RF10_PUBLIC_BOT.md).

Actualización solicitada después de la primera entrega: bot público con aspecto WhatsApp y perfil por nombre/tipo/número de documento de prueba. Esta petición modifica la restricción inicial que excluía documentos del modelo; se conserva el laboratorio de datos ficticios. Se usa el mismo ID interno de paciente antes y después de activar su web para conservar citas y auditoría. La clave adicional evita convertir un identificador conocido en una credencial de acceso. El número se cifra y la unicidad se aplica a una clave HMAC normalizada por tipo y número. El registro no modifica identidades existentes sin sesión verificada o clave previa.

Los nombres de documentos se contrastaron con fuentes oficiales de [MinSalud](https://www.minsalud.gov.co/sites/rid/Lists/BibliotecaDigital/RIDE/VP/RBC/solicitud-informacion-anual-upc-2025.pdf) y [Supersalud](https://normograma.supersalud.gov.co/compilacion/docs/resolucion_minsaludps_2646_2024.htm). El catálogo de la aplicación es de laboratorio; no se presenta como un validador oficial ni una implementación completa de RIPS.

| Decisión | Motivo y consecuencia |
| --- | --- |
| Laravel 13 + React 19 + Inertia 3 + Tailwind 4 | Versiones disponibles y compatibles al instalar. Laravel 13 necesita PHP 8.3+; se usa PHP 8.4.27. Dependencias exactas en locks |
| Mínimo del prototipo PHP 8.4.1 | Las versiones Symfony del lock requieren 8.4.1. El requisito de Composer refleja esa resolución; la instalación reproducible usa 8.4.27 |
| PostgreSQL 18 real | JSONB, timestamps con zona y unicidad parcial. No se simula persistencia en navegador ni se sustituye por SQLite |
| `embedded-postgres` como herramienta local | El equipo no tenía PostgreSQL. El paquete beta fija binarios PostgreSQL 18 y evita instalación de servicio global; no es una dependencia de negocio ni el método propuesto para producción |
| PHP portátil local | El equipo no tenía PHP/Composer. Se instaló dentro de `.runtime`, excluido de Git; la instalación Windows verifica la suma oficial de PHP |
| Monolito modular | Una sola implementación de reglas para web y conversación. Sin microservicios, APIs sanitarias o mensajería real |
| UUID de solicitud y código | Evita duplicar una reserva por reintento y permite mostrar código ficticio sin información personal |
| Índice único parcial y bloqueos | La regla de una cita activa tiene defensa en SQL y en transacciones. Auditoría participa del commit |
| Roles mínimos | Usuario gestiona únicamente sus citas; administrador gestiona franjas, auditoría y reportes. Sin rol clínico |
| America/Bogota explícita en tres capas | Laravel, conexión SQL y formateadores de interfaz interpretan la fecha de la misma manera |
| Recordatorio como evento interno | Clave única por cita/franja/inicio, reloj configurable; sin correo, teléfonos ni proveedores externos |
| Reporte por fecha programada | El periodo representa la agenda actual; movimientos históricos se consultan en auditoría |
| Inter y Lucide locales | Identidad consistente; sin descarga de fuentes o iconos desde terceros durante el uso |
| Texto secundario #526475 | Ajuste de la paleta inicial para asegurar lectura en superficies claras |
| Controles y estados convencionales | Calendario mediante botones, selects nativos y diálogo nativo; teclado y foco visibles. Presión discreta solo como respuesta, sin coreografías de entrada |
| Sesión de 15 minutos y límites solo a fallos de acceso | Cumple el maestro y evita penalizar entradas válidas durante una demostración |
| Guía de despliegue, sin publicación | El usuario pidió local y despliegue posterior; no especificó servidor. Se prepara una configuración revisable, marcada como no ejecutada |

Fuentes de compatibilidad: [Laravel 13](https://laravel.com/framework/docs/13.x/releases), [instalación Inertia 3](https://github.com/inertiajs/docs/blob/main/v3/installation/server-side-setup.mdx), manifiestos resueltos y comandos realmente ejecutados. No se atribuyen pruebas de Docker ni validación humana inexistentes.
