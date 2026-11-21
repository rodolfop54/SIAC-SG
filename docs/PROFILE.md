# Perfil del portal

Actualización del 20 de noviembre de 2026. El portal de pacientes tiene **Disponibilidad**, **Mis citas** y **Mi perfil**. Se retiró **Bot público** del menú privado; el bot sigue disponible desde su entrada pública `/bot` y comparte el mismo paciente y sus citas.

## Edición del nombre

`GET /perfil` muestra los datos de la sesión autenticada. `PATCH /perfil` acepta exclusivamente el nombre (3–100 caracteres, con letras). El identificador del paciente, documento, rol, contraseña, correo y citas no se obtienen de ese formulario. La navegación muestra el nombre de la persona, con recorte visual para nombres extensos, en lugar del alias interno.

El documento aparece enmascarado y no es editable porque vincula el historial. El cambio del nombre se guarda junto con su evento de auditoría en una transacción. La auditoría registra el campo actualizado y el actor, sin copiar el nombre ni el correo personales.

## Cambio de correo

1. Confirmar el acceso si no se hizo en los últimos cinco minutos: contraseña actual o código enviado al correo ya verificado. Las cuentas de demostración sin documento confirman con contraseña.
2. Escribir el nuevo correo y solicitar el código. La dirección anterior se conserva y continúa sirviendo para entrar y recuperar el acceso.
3. Introducir el código del nuevo buzón. Solo entonces se reemplaza el correo, se marca verificado y se actualiza la versión de autenticación.
4. La sesión que confirmó el cambio continúa abierta. Otras sesiones y códigos anteriores quedan invalidados por la versión de autenticación; sus siguientes solicitudes deben volver a identificar al paciente. Paciente, documento, contraseña y citas se conservan.

El código vence en diez minutos, admite cinco intentos y solo se utiliza una vez. Se almacena como HMAC; los datos de la solicitud y el correo permanecen cifrados. Las solicitudes tienen límites por cuenta e IP. Cancelar el cambio invalida la solicitud y conserva la dirección actual. Un error del proveedor no actualiza el correo.

Los códigos de alta, acceso, recuperación y cambio de correo tienen propósitos distintos: no son intercambiables. Las rutas requieren sesión, rol de paciente y CSRF; el dueño procede de la sesión, no de un `user_id` enviado desde la pantalla. Los códigos y contraseñas no se guardan como valores anteriores del formulario.

## Contraseña y demostración

Los pacientes con documento y correo verificado tienen acceso al enlace existente para elegir o recuperar su contraseña; el titular la introduce personalmente. Si falta el correo, la pantalla indica que se asocie y verifique primero. Las cuentas de demostración conservan su acceso por alias y contraseña; asociarles un correo de perfil no las convierte en perfiles con documento.

El correo de Render utiliza la configuración privada de Brevo ya existente. No se copian claves a GitHub. Las instalaciones locales conservan `PATIENT_EMAIL_ACCESS=false` hasta configurar su proveedor; el nombre puede editarse sin envío de correos.

## Verificación y alcance

`ProfileTest` comprueba aislamiento, documento y roles protegidos, conservación de citas, confirmación reciente por contraseña o código, verificación del correo nuevo, expiración, cinco intentos, uso único, cancelación, fallos de envío e invalidación de accesos anteriores. `profile.spec.ts` comprueba la navegación, persistencia del nombre, capturas, reflujo y accesibilidad automática a 360, 768 y 1366 px.

La suite de servidor intercepta Brevo: no envía correos a direcciones de prueba. La comprobación del cambio completo de una dirección real requiere un nuevo buzón proporcionado y controlado por el titular; no se cambia el correo del propietario para obtener una captura. Los resultados ejecutados y su evidencia figuran en [TEST_RESULTS.md](TEST_RESULTS.md). Estas pruebas técnicas no acreditan una evaluación con diez participantes ni una validación TRL.
