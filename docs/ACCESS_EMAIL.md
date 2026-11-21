# Cuenta compartida y acceso por correo

Actualización del 20 de noviembre de 2026. El bot y la web usan la misma cuenta y las mismas citas. El envío por Brevo está implementado, pero queda pendiente de activar y de comprobar con un correo recibido realmente. Las pruebas automáticas interceptan la API y no constituyen evidencia de entrega externa.

## Recorridos

- Primera visita al bot con correo activado: tipo y documento LAB/FICT → nombre ficticio → correo → código de seis dígitos → menú de citas. No se crea ningún paciente antes de verificar el código.
- Visita posterior: tipo y documento → código al correo ya verificado → menú. Indicar otro correo no cambia el destinatario de una cuenta verificada.
- **Entrar a mi espacio web** desde el bot verificado abre `/mis-citas` con el mismo paciente y sus reservas.
- Portal: `/acceso` conserva documento y contraseña; `/acceso/codigo` permite entrar sin contraseña y `/registro` lleva al alta con verificación por correo cuando está activado.
- `/recuperar` envía un enlace para crear o cambiar la contraseña de una cuenta con correo verificado. El usuario elige la contraseña; nunca se envían contraseñas temporales ni actuales.
- Una cuenta anterior sin correo exige la contraseña actual o una sesión verificada vigente de ese mismo paciente antes de asociar un correo. La asociación no modifica la contraseña ni las citas existentes.
- Mientras el envío esté desactivado, el bot conserva su recorrido de clave, la misma clave funciona en la web y las pantallas de correo explican que la activación está pendiente.

## Servicio gratuito y Render

Render Free bloquea conexiones salientes a SMTP 25, 465 y 587. La aplicación llama por HTTPS a `POST https://api.brevo.com/v3/smtp/email`, sin usar SMTP ni cambiar a un plan pago. Brevo anuncia 300 envíos diarios en su plan gratuito; cada solicitud de código o enlace consume un envío. El proveedor puede requerir revisión de la cuenta o autenticación del remitente.

1. Crear una cuenta gratuita en [Brevo](https://www.brevo.com/), verificar el correo del propietario y completar los datos solicitados.
2. En la configuración de remitentes, crear **SIAC-SG** con una dirección que el propietario controle y verificarla mediante el mensaje recibido. Si el proveedor exige autenticar un dominio, completar esa verificación antes de activar el acceso. Con direcciones gratuitas como Gmail, Brevo puede reescribir el remitente para cumplir sus políticas; comprobar el remitente efectivo en una entrega de prueba.
3. Crear una clave de **API v3**, no una clave SMTP. No pegarla en el chat, capturas ni archivos versionados.
4. En [Environment del servicio SIAC-SG](https://dashboard.render.com/web/srv-dbrrvkm1egvs73c3a91g/env), agregar las siguientes variables. Conservar todas las variables existentes y el mismo `APP_KEY`.

| Variable | Valor |
| --- | --- |
| `PATIENT_EMAIL_ACCESS` | `true`, únicamente después de preparar el remitente y la clave |
| `BREVO_API_KEY` | Clave API v3 privada, guardada solo en Render |
| `MAIL_FROM_ADDRESS` | Dirección del remitente verificado en Brevo |
| `MAIL_FROM_NAME` | `SIAC-SG` |

`MAIL_MAILER=log` puede mantenerse: este flujo utiliza directamente la API de Brevo y no escribe el código en el registro de correo de Laravel. Para ejecución local, guardar las mismas variables en `.env`, excluido de Git, y ejecutar `php artisan optimize:clear`. Render reconstruye su configuración al arrancar; guardar las variables y desplegar la versión actual para aplicarlas.

## Comprobación pendiente de entrega real

1. Crear un paciente LAB/FICT nuevo usando un correo propio del evaluador. Confirmar recepción, revisar spam y comprobar el registro transaccional de Brevo.
2. Introducir el código en el bot, reservar una franja y entrar al portal mediante el botón. Verificar que aparece el mismo código de reserva.
3. Salir y volver al bot con el mismo documento: debe solicitar un código, sin repetir el registro.
4. Entrar en la web con código. Solicitar el enlace para elegir contraseña; comprobar el dominio HTTPS del enlace y entrar luego con la nueva contraseña.
5. Verificar rechazo de códigos/enlaces usados, vencidos y de una contraseña anterior. Conservar capturas sin códigos vigentes, claves, correos completos ni documentos personales.

Solo después de realizar estos pasos puede registrarse el envío real como verificado. Si Brevo acepta un mensaje, eso no garantiza por sí solo que haya llegado a la bandeja del destinatario.

## Protección y límites

Los códigos/enlaces vencen en diez minutos, admiten como máximo cinco intentos y se consumen una sola vez, bajo transacción y bloqueo SQL. Se almacenan como HMAC, con datos del desafío y correos cifrados. La solicitud se limita a una por minuto por documento y propósito y diez por hora por IP, además de las restricciones de las rutas. La recuperación usa una respuesta genérica para documentos sin correo verificado. Los fallos del proveedor no crean cuentas ni conceden acceso.

Cambiar la contraseña invalida códigos pendientes y versiones anteriores de sesiones web/bot; los datos de identificación y secretos no se añaden al historial del chat ni a entradas antiguas de los formularios. El enlace de recuperación lleva `Referrer-Policy: no-referrer` y `Cache-Control: no-store, private`. Las cuentas de demostración y el administrador conservan su acceso por alias.

Verificar correo demuestra control de ese buzón, no identidad civil ni validez de una cédula. Se mantienen documentos y pacientes ficticios; no hay consulta a Registraduría ni integración con WhatsApp. Este incremento no acredita una evaluación de usabilidad con diez participantes ni TRL 5.

## Fuentes de configuración

- [Render Free: restricciones SMTP](https://render.com/docs/free)
- [Brevo: límites del plan gratuito](https://help.brevo.com/hc/en-us/articles/208580669-FAQs-What-are-the-limits-of-the-Free-plan)
- [Brevo: correo transaccional por API](https://developers.brevo.com/docs/send-a-transactional-email)
- [Brevo: crear y verificar remitentes](https://help.brevo.com/hc/en-us/articles/208836149-Create-a-new-sender-From-name-and-From-email)
- [Brevo: requisitos de Gmail, Yahoo y Microsoft](https://help.brevo.com/hc/en-us/articles/14925263522578-Comply-with-Gmail-Yahoo-and-Microsoft-s-requirements-for-email-senders)
