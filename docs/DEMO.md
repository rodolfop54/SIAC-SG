# Guion de demostración

Inicia `scripts/start.ps1` y abre http://127.0.0.1:8000. Consulta los accesos en `.runtime/demo-access.txt`.

1. Explica el aviso de laboratorio y la ausencia de integración sanitaria.
2. Entra como DEMO-ALBA. Selecciona servicio, fecha futura con punto verde y horario. Muestra que la selección no reserva.
3. Revisa y confirma. Copia el código y recarga: la cita sigue guardada en PostgreSQL.
4. Abre Mis citas. Reprograma a otro horario del mismo servicio y comprueba que conserva código.
5. Abre la confirmación de cancelación. Pulsa Escape para conservar. Repite y confirma: estado Cancelada, historial conservado y franja nuevamente disponible.
6. En una ventana sin sesión, abre `/bot`. Elige CC, TI u otro tipo, escribe un identificador sintético único como `LABDEMO001`, nombre completo de prueba y una clave; confírmala. No uses documentos reales ni números sin prefijo LAB/FICT. Reserva sin cuenta web. Usa 1 para reservar, 2 para consultar, 3 para cambiar y 4 para cancelar. Menú/0/salir funcionan también durante la identificación.
7. Pulsa «Activar mi cuenta web». Regístrate con el mismo documento y comprueba que la cita está en Mis citas. Desde otro navegador, la vinculación pide la clave anterior del bot. Para ingresar después, usa documento y contraseña; la pestaña «Cuentas de demostración» conserva los alias anteriores.
8. Escribe una opción inválida: no se crea una reserva y aparece recuperación. Escribe menú o 0 para salir del contexto.
9. Ejecuta `php artisan siac:recordatorios` (en Windows usa `.runtime\php\php.exe`) o la opción 5. Solo se crean mensajes de citas de próximas 24 horas; repetir no los duplica. Si la agenda actual no tiene citas dentro de ese intervalo, usa `--at` con un reloj sintético anterior a una reserva de prueba.
10. Cierra sesión y entra como DEMO-ADMIN. Crea una franja futura sin cruces. Filtra fecha y servicio para encontrarla; recuerda la paginación. Bloquea y vuelve a habilitar una franja libre.
11. Intenta bloquear una franja ocupada: se muestra el conflicto y la reserva se conserva. Consulta los eventos en Auditoría y los conteos por periodo/servicio en Reportes. Exporta CSV.
12. Muestra capturas móviles y las pruebas técnicas ejecutadas. Distingue resultados automáticos de la futura evaluación con diez participantes.

Para una demostración posterior con semilla reciente, ejecuta `php artisan db:seed`: agrega franjas relativas sin borrar historial ni cambiar contraseñas. La preparación completamente limpia debe realizarse en una base nueva; no se necesita borrar la base de trabajo.
