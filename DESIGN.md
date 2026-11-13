---
name: SIAC-SG
description: Laboratorio académico de gestión de citas, verde y blanco.
colors:
  green: "#166534"
  green-dark: "#14532D"
  green-hover: "#15803D"
  green-light: "#DCFCE7"
  green-soft: "#EDF7F0"
  ground: "#F7FBF8"
  surface: "#FFFFFF"
  ink: "#0F172A"
  muted: "#526475"
  border: "#E2E8F0"
  field-border: "#C9D6CE"
  danger: "#B42318"
  danger-soft: "#FFF1F0"
typography:
  headline:
    fontFamily: "Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: "30px"
    fontWeight: 600
    lineHeight: 1.2
    letterSpacing: "-0.035em"
  title:
    fontFamily: "Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: "18px"
    fontWeight: 600
    lineHeight: 1.4
    letterSpacing: "-0.02em"
  subtitle:
    fontFamily: "Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: "15px"
    fontWeight: 600
    lineHeight: 1.55
  body:
    fontFamily: "Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: "14px"
    fontWeight: 400
    lineHeight: 1.55
  action:
    fontFamily: "Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: "13px"
    fontWeight: 600
    lineHeight: "20px"
  label:
    fontFamily: "Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: "12px"
    fontWeight: 600
    lineHeight: 1.55
rounded:
  tag: "5px"
  field: "7px"
  control: "8px"
  bubble: "9px"
  panel: "12px"
  dialog: "14px"
  round: "50%"
spacing:
  xs: "4px"
  sm: "8px"
  md: "12px"
  lg: "16px"
  xl: "20px"
  xxl: "24px"
  section: "30px"
  workspace: "38px"
components:
  button-primary:
    backgroundColor: "{colors.green}"
    textColor: "{colors.surface}"
    typography: "{typography.action}"
    rounded: "{rounded.control}"
    padding: "11px 18px"
  button-primary-hover:
    backgroundColor: "{colors.green-hover}"
  button-secondary:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    typography: "{typography.action}"
    rounded: "{rounded.control}"
    padding: "11px 18px"
  button-secondary-hover:
    backgroundColor: "{colors.green-soft}"
  button-danger:
    backgroundColor: "{colors.danger}"
    textColor: "{colors.surface}"
    typography: "{typography.action}"
    rounded: "{rounded.control}"
    padding: "11px 18px"
  button-danger-quiet:
    backgroundColor: "transparent"
    textColor: "{colors.danger}"
    rounded: "{rounded.control}"
    padding: "11px 18px"
  input:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    rounded: "{rounded.field}"
    padding: "10px 12px"
  navigation-active:
    backgroundColor: "{colors.green-soft}"
    textColor: "{colors.green}"
    rounded: "{rounded.control}"
    padding: "12px 14px"
  badge-confirmed:
    backgroundColor: "{colors.green-soft}"
    textColor: "{colors.green}"
    rounded: "{rounded.tag}"
    padding: "5px 9px"
  panel:
    backgroundColor: "{colors.surface}"
    rounded: "{rounded.panel}"
  slot-selected:
    backgroundColor: "{colors.green-soft}"
    textColor: "{colors.green}"
    rounded: "{rounded.field}"
    padding: "10px 11px"
  bubble-incoming:
    backgroundColor: "{colors.surface}"
    rounded: "{rounded.bubble}"
    padding: "12px 14px"
---

# Design System: SIAC-SG

## Overview

**Creative North Star: "La agenda clara"**

El sistema conserva la identidad verde y blanca fijada por el usuario: una aplicación profesional, sobria y práctica. Inter, iconos de trazo Lucide, superficies blancas y fondos apenas verdes permiten leer horarios, estados y acciones sin decoración que compita con ellos. El nombre resume el contrato confirmado; no procede de un catálogo remoto ni de una composición aprobada.

La densidad es operativa: textos breves, datos alineados y espacios consistentes entre grupos. Seleccionar, revisar y confirmar son estados visibles distintos. El aviso de laboratorio académico y de datos ficticios permanece en todas las rutas y forma parte de la identidad del producto.

**Key Characteristics:**

- Verde institucional para acciones y selección; blanco para superficies de trabajo.
- Jerarquía tipográfica contenida y números tabulares en tablas y códigos.
- Contenedores delimitados por bordes suaves, con sombras reservadas a superposiciones.
- Estados explicados con palabras e iconos, además de color.
- Respuesta breve de controles y respeto por movimiento reducido.

Registro extraído de `resources/css/app.css`, `Layout.tsx`, `UI.tsx` y las páginas Availability, Bot y Reports. Dirección `seed:70050168a9a04f1f`, corroborada en `.impeccable/questions/roll.json`. El frontmatter es normativo; el sidecar extiende sus tokens con movimiento, puntos de quiebre y muestras de componentes.

## Colors

Un único acento verde organiza la acción sobre blancos y neutros de baja saturación. El rojo se reserva a errores y acciones destructivas.

### Primary

- **Verde institucional** (`green`): acción principal, fecha elegida, iconos y barras de reportes.
- **Verde profundo** (`green-dark`): cabecera del asistente y texto de éxito.
- **Verde de interacción** (`green-hover`): hover, foco visible e indicadores de disponibilidad.
- **Verde de confirmación** (`green-light`): avisos de éxito y selección de texto.
- **Verde suave** (`green-soft`): navegación activa, horario seleccionado, insignias y avisos.

### Neutral

- **Fondo de trabajo** (`ground`): plano general y encabezados de tabla.
- **Blanco de superficie** (`surface`): paneles, campos y navegación.
- **Tinta** (`ink`): contenido principal.
- **Texto secundario legible** (`muted`): ayudas y metadatos; conserva el ajuste de contraste implementado.
- **Borde discreto** (`border`) y **borde de campo** (`field-border`): delimitación de regiones y controles.
- **Rojo de error** (`danger`) y **fondo de error** (`danger-soft`): mensajes, campos inválidos y cancelación.

**The Estado explícito Rule.** El color acompaña una etiqueta o un icono; nunca es la única explicación de selección, confirmación, cancelación o error.

## Typography

**Display Font / Body Font:** Inter, con respaldo `ui-sans-serif, system-ui, sans-serif`.

**Character:** Una sola familia mantiene continuidad entre navegación, formularios y datos. La jerarquía depende de tamaño, peso y espacio; los pesos habituales son 400, 500, 600 y 700.

### Hierarchy

- **Headline:** título de página según `headline`; a 700px o menos baja a 26px.
- **Title:** sección según `title`; las secciones densas emplean variantes de 14–16px en calendario, agenda y reportes.
- **Subtitle:** encabezados menores según `subtitle`.
- **Body:** texto general según `body`; las descripciones de página usan 13px y hasta 72ch.
- **Action:** botones según `action`.
- **Label:** etiquetas según `label`; ayudas y metadatos secundarios varían entre 10 y 12px.

La portada pública usa una escala propia: título de 58px, peso 600 y línea 1.13, que desciende a 39px en el punto de quiebre más estrecho. Esta excepción no se traslada a las pantallas operativas. Códigos, tablas y horarios usan números tabulares; `code` conserva la fuente monoespaciada nativa.

## Layout

El escritorio combina una barra lateral fija (236px), cabecera (78px) y área principal centrada con ancho máximo de 1440px. El relleno del área principal es 38px. Los pasos frecuentes de 4, 8, 12, 16, 20 y 24px conviven con ajustes locales de 18, 22, 25 y 26px: no se impone una cuadrícula rígida de múltiplos de cuatro.

La reserva divide contenido y resumen en una columna flexible y otra de 274px, separadas 22px. El resumen es sticky a 24px del borde superior. A 1180px su columna baja a 235px y calendario/horarios se apilan. A 900px el resumen continúa debajo y deja de ser sticky; el bot también pasa a una columna.

A 900px la barra lateral mide 205px y el relleno principal baja a 24px. A 700px la navegación se abre desde la cabecera, el contenido ocupa todo el ancho y el relleno lateral es 20px. A 430px calendario y horarios se apilan y los horarios forman dos columnas. Desde 1500px crecen el relleno y la columna de resumen. Los filtros se envuelven; las tablas permanecen en regiones desplazables con nombre accesible y foco de teclado.

**The Orden continuo Rule.** Al estrecharse la pantalla, el contenido conserva su orden de lectura y el resumen continúa debajo de la selección.

## Elevation & Depth

Los paneles descansan planos. Fondo, superficie, borde y verde suave aportan separación. Las sombras se reservan al diálogo de cancelación y al menú móvil superpuesto.

### Shadow Vocabulary

- **Diálogo:** `0 16px 60px #0f172a26`; fondo modal `#10231bb3`.
- **Menú móvil:** `0 14px 22px #10231b1a`.

**The Superposición Rule.** La sombra comunica una capa superpuesta; los paneles normales conservan su borde y no heredan elevación.

## Shapes

Los paneles usan `rounded.panel`; botones y navegación, `rounded.control`; campos y horarios, `rounded.field`; etiquetas, `rounded.tag`. El diálogo es ligeramente más redondeado. Avatares e indicadores son circulares. Las burbujas usan `rounded.bubble`, con la esquina superior del lado del emisor reducida a 2px para indicar dirección. Los bordes son de 1px.

Iconos Lucide de trazo, normalmente entre 14 y 25px, acompañan el texto. Las acciones de solo icono tienen nombre accesible. La aplicación operativa no emplea imágenes decorativas.

## Components

### Buttons

Acciones contenidas, con altura mínima habitual de 44px. El primario usa verde y blanco; el secundario, blanco, tinta y borde; peligro usa rojo sólido o texto rojo sobre transparente. La variante compacta mide al menos 40px. Agenda y paginación conservan excepciones densas de 36px; no son la regla para controles nuevos.

Hover cambia el fondo; pulsar reduce a escala 0.98. La transición dura 140ms, con `ease` para color y `cubic-bezier(0.23, 1, 0.32, 1)` para transformación. El foco tiene contorno verde de 3px separado 3px. Los estados pendientes deshabilitan la acción y muestran progreso. Movimiento reducido elimina transición y escala; sin hover no se conserva el fondo de hover.

### Chips

Insignias informativas. Confirmada combina verde suave, texto verde y verificación; Cancelada usa fondo neutro y texto secundario. Ambas explican el estado con palabras.

### Cards / Containers

Panel blanco con borde discreto y esquinas suaves. El relleno suele ser 20–26px; el resumen de selección de escritorio usa 23px. Las divisiones internas usan líneas suaves; los fondos coloreados se reservan a avisos y apoyos.

### Inputs / Fields

Campos y selectores blancos, texto de 13px, borde propio, altura mínima de 44px y relleno según `input`. Etiqueta encima, separación de 8px y ayuda debajo. El foco conserva el contorno global. Un campo inválido tiene borde rojo y mensaje vinculado; el resumen de errores recibe foco y se anuncia como alerta.

### Navigation

Enlaces con icono, etiqueta, altura mínima de 46px y esquinas suaves. Activo combina verde suave, texto verde, peso 600, punto al extremo y `aria-current`. El botón móvil expone su estado expandido; el menú se cierra al cambiar de ruta. El enlace para saltar al contenido aparece al recibir foco.

### Calendar / Selection

Fecha elegida verde sólido con texto blanco. Disponibilidad se indica con punto y nombre accesible. Horario seleccionado combina fondo suave, borde verde, verificación y “Seleccionado”. El resumen muestra servicio, fecha, hora y cuenta ficticia. “Revisar horario” precede a confirmar; solo la confirmación persistente muestra código. Un conflicto no se presenta como confirmación.

### Tables / Reports

Encabezados sobre el fondo de trabajo, filas separadas por líneas y números tabulares. La región desplazable mantiene encabezados y datos juntos. Las cifras agregadas preceden al gráfico; las barras verdes son estáticas, con total visible y alternativa textual accesible.

### Guided conversation

Cabecera verde profundo y conversación sobre fondo gris verdoso. Burbujas blancas a la izquierda y verdes suaves a la derecha, con autor, contenido y hora. Opciones guiadas con borde, etiqueta y altura de 44px. El registro anuncia adiciones y conserva las opciones del último mensaje de conversación aunque llegue un recordatorio interno. El compositor mantiene envío junto al campo.

## Do's and Don'ts

### Do:

- **Do** conservar la identidad verde y blanca y el aviso visible de laboratorio académico.
- **Do** acompañar estados con texto e iconos y mantener el foco visible.
- **Do** separar selección temporal, revisión y confirmación persistente.
- **Do** mantener las tablas dentro de regiones desplazables etiquetadas.
- **Do** respetar movimiento reducido y usar la respuesta breve de los controles existentes.

### Don't:

- **Don't** introducir otro acento de marca ni reemplazar Inter sin una decisión explícita de identidad.
- **Don't** convertir la selección de un horario en una confirmación visual de reserva.
- **Don't** aplicar sombras de superposición a todos los paneles.
- **Don't** añadir animaciones de entrada de página o decoración que compita con la agenda.

No canonizado: las excepciones densas de 36px y la microtipografía de 8–10px son límites observados, no objetivos para nuevas superficies. Este pase documenta sin modificar producto; la validación visual y de accesibilidad corresponde al informe de cierre.

## Variante pública del bot: aspecto WhatsApp

Solicitada expresamente después de la primera entrega. El bot abandona el layout privado y usa `PublicLayout`, con acceso anónimo y registro web opcional. El chat conserva la identificación SIAC-SG; no se presenta como una integración con Meta.

Tratamiento específico: cabecera #075E54, acción #008069, lienzo #EFEAE2, mensajes entrantes blancos y salientes #D9FDD3, metadatos #54656F y compositor #F0F2F5. Tipografía del mensaje 13px, autor 11px y hora 10px. El resto de la aplicación conserva sus tokens existentes. Las colas triangulares pequeñas son parte de las burbujas de conversación, no acentos de borde en tarjetas.

En la identificación inicial se mantiene el scroll arriba para leer el saludo. Las opciones CC/TI/RC/CE/PA/PPT se agrupan en dos columnas y los tipos restantes en un desplegable nativo. Al recibir mensajes normales se desplaza al resultado. Nombre y documento se solicitan en pasos; los números completos y claves no se repiten en las burbujas. Los campos de clave usan input password.

Se verificaron el chat y el registro a 360/768/1366px con capturas y Axe; no se añadieron controles inactivos de llamada, cámara o adjuntos.
