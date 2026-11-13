# Revisión visual y de interacción

## Ampliación pública con aspecto WhatsApp

Se inspeccionaron las capturas `whatsapp-bot-360.png`, `whatsapp-bot-1366.png` y `registration-360.png`. Se corrigieron dos hallazgos: el scroll inicial ocultaba saludo y CC/TI, y el título móvil del registro unía palabras al ocultar el salto de línea. Se recapturaron los tres tamaños y pasaron las comprobaciones automáticas correspondientes.

El detector de esta ampliación dejó dos avisos `border-accent-on-rounded` sobre los triángulos de cola de mensajes: se revisaron como falsos positivos porque son pseudoelementos pequeños propios de la conversación solicitada, no bordes gruesos de tarjetas. También emitió avisos tipográficos frente a la rampa reducida documentada; incluyen tamaños existentes de microtexto y tamaños de la variante de chat descritos en DESIGN.md. Este registro no los presenta como un detector vacío ni como una nueva revisión independiente. La revisión independiente que sigue corresponde al alcance de la entrega anterior.

Se aplicaron las instrucciones instaladas de Emil Kowalski e Impeccable. Referencias y alcance en DESIGN_REFERENCES. La revisión independiente se realizó sobre código y 19 capturas reales; no controló otro navegador ni repitió por sí misma las pruebas E2E.

| Before | After | Why |
| --- | --- | --- |
| Texto secundario inicial #64748B | Token #526475 | Aumentar legibilidad en las superficies claras |
| Límite que consumía accesos válidos | Límite sobre fallos de alias/IP | La protección no interrumpe una demostración con cuentas válidas |
| Gráfico horizontal sin parada de teclado | Región de gráfico con `tabIndex=0` y descripción | Permitir desplazamiento por teclado; hallazgo Axe corregido |
| Último mensaje del bot, incluyendo recordatorios, determinaba opciones | Último mensaje conversacional determina opciones | Un recordatorio no interrumpe el recorrido guiado |
| Transcripción con solo recordatorios no recibía saludo | Saludo de identificación cuando no existe conversación | Siempre hay una acción válida para iniciar |
| Acciones de cabecera comprimidas a 768px | Acción principal sin encogimiento | Evitar etiquetas partidas en botones de tableta |
| Todas las variantes de fuente empaquetadas | Inter Latin 400/500/600/700 local | Mantener apariencia reduciendo assets/CSS innecesarios |
| Salto de línea oculto unía «Bienvenidoa» en móvil | Espacio explícito entre las palabras | Conservar la separación al replegar la cabecera de acceso |

El detector mecánico `detect --json` devolvió `[]`. La suite Chrome verificó Axe, reflujo y movimiento reducido; también Escape y foco del diálogo de cancelación. La animación se limita a presión de botones de 140ms, sin entradas de página o números animados.

## Revisión independiente

La primera disposición fue **fix**: las tres capturas del bot mostraban saludo y recordatorio sin opciones. El revisor confirmó coherencia de tipografía, material, fondo, aviso, composición de disponibilidad y espacios administrativos. No pidió rediseño o decoración adicional.

Después de corregir código y pruebas de regresión, se reemplazaron `bot-360.png`, `bot-768.png` y `bot-1366.png`. El revisor observó la acción «Soy DEMO-ALBA» después del saludo y del recordatorio. Resultado de la pasada de veredicto: **resolved; remaining clear; disposition ship**, limitado expresamente al hallazgo corregido. No equivale a certificación total de accesibilidad o seguridad.

No existe un comp de imagen aprobado ni QUALITY BAR card: la dirección verde/blanco y la estructura funcional venían fijadas por el encargo; el catálogo remoto de conceptos no estuvo accesible. Las capturas son evidencia de implementación y no una supuesta comparación pixel a pixel con Figma.
