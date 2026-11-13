# Referencias consultadas y decisiones

Actualización por solicitud explícita: `/bot` ahora es público y usa un tratamiento visual de WhatsApp (cabecera verde, fondo cálido, burbujas blancas/verdes con cola, horas y doble marca de recepción local, respuestas rápidas y compositor redondeado). No se integra el servicio ni se muestran botones ficticios de llamada o adjuntos. Se conserva la identidad SIAC-SG y la indicación de simulación. Se revisaron capturas a 360, 768 y 1366px; se corrigió el scroll inicial para mantener visibles el saludo y las opciones CC/TI.

Consulta realizada durante la implementación del 12 de noviembre de 2026 (Colombia).

## Figma

Archivo accesible: [SIAC-SG](https://www.figma.com/design/CYewKJeuimBbjUCrCKMslf). Se enumeraron las tres páginas y sus metadatos completos:

| Fuente | Consulta real | Decisión aplicada |
| --- | --- | --- |
| 00 Guía y controles, `0:1` | Guía, alcance ficticio, recorridos y controles | Aviso permanente, roles, 360/768/1366 y separación entre selección y reserva |
| 01 Web y administración, `2:16` | Metadatos de pantallas; contexto de diseño y captura de U02 `3:46` | Navegación lateral, calendario/horarios, resumen lateral, tres pasos y estados reutilizables |
| 02 Bot simulado, `2:17` | Metadatos de B00–B13 y variantes; contexto y captura de B01 `5:24` | Conversación con opciones guiadas, revisión, resultado, recuperación, menú y salida |

Se modernizó la baja fidelidad siguiendo la instrucción del usuario: Inter, Lucide, verde/blanco, jerarquía y espaciado consistentes. No se incorporaron datos ilustrativos de Figma como fechas o usuarios de operación. Los iconos del bosquejo se sustituyeron por la biblioteca Lucide expresamente solicitada; no hay capturas ni URL temporales de Figma dentro de la aplicación.

## Mobbin

Se leyeron las instrucciones instaladas de mejores prácticas y búsqueda. Se intentó una consulta web de calendarios de reserva médica mediante el conector. **El conector rechazó la consulta porque requiere un plan de pago. No se recibieron ni analizaron pantallas de Mobbin.**

La guía de Figma menciona Selfridges, Fresha y Meetup; eso es contenido de la guía, no prueba de una consulta independiente de esas aplicaciones. No se atribuyen decisiones a pantallas de Mobbin que no estuvieron accesibles.

## Skills aplicadas

`emil-design-eng`: se leyó el marco de decisión de animación; no se animan páginas, calendarios ni cifras. Los botones tienen presión discreta de 140ms, y `prefers-reduced-motion` la desactiva. Diálogo nativo de cancelación, foco inicial en conservar y retorno al disparador; estados de progreso y controles deshabilitados durante escrituras.

`impeccable` versión 4.8.2: se ejecutó `context`; se leyeron `init`, `new-work`, `craft-floor`, `operate`, `audit` y `polish`. Se ejecutó `concept-seed`; no accedió a su catálogo remoto. Se respetó la dirección verde/blanco ya fijada por el usuario. Se registraron producto, sistema visual y contrato de superficie. `detect --json resources/js resources/css` devolvió `[]`; su alcance mecánico no sustituye revisión visual. La revisión independiente del flujo de finalización detectó y permitió corregir que un recordatorio ocultara las opciones del bot.

La evidencia está en `output/playwright`, `output/verification` y la revisión visual registrada en la documentación. Las auditorías automáticas se limitaron a los criterios y rutas comprobados, sin certificar WCAG AA completa.
