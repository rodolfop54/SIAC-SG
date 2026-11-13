# SIAC-SG
<!-- impeccable:product-schema 1 -->

## Platform
web

## Stack
Laravel 13, React 19, TypeScript, Inertia 3, Tailwind 4 y PostgreSQL 18, solicitados explícitamente.

## Users
Pacientes de prueba que reservan desde un bot público sin cuenta web previa y pueden activar después su cuenta; administradores que gestionan agendas, auditoría y reportes. La identidad del paciente se vincula por tipo y número de documento ficticio.

El identificador de laboratorio exige prefijo LAB o FICT. Conocerlo no concede acceso: los perfiles existentes requieren clave. Las acciones del chat pertenecen al estado de la sesión y nunca se obtienen de opciones antiguas del historial. El prototipo no acredita identidad civil ni admite uso con pacientes reales.

## Product Purpose
Laboratorio académico funcional de gestión de citas de primer nivel para la Fase 4 de Ingeniería de Sistemas de la UNAD. No presta servicios sanitarios.

## Capabilities and Constraints
Disponibilidad, reserva, historial, reprogramación, cancelación, agenda, auditoría, reportes y bot público con aspecto WhatsApp y recordatorios internos. Registro web opcional. Nombre, tipo y número de documento de prueba compartidos entre ambos canales; número cifrado, índice HMAC único y clave de acceso para impedir que un documento por sí solo dé acceso a las citas. Datos sintéticos persistentes, sin historias clínicas ni integraciones sanitarias o de mensajería. America/Bogota. Sesiones con 15 minutos de inactividad. Este cambio de identificación fue solicitado expresamente después de la primera implementación.

## Brand Commitments
Nombre SIAC-SG; verde y blanco, minimalista, profesional; flujos de Figma como referencia estructural. Aviso permanente de simulación.

## Evidence on Hand
Documento maestro en docs/Fase3_SIAC_SG_Grupo9.pdf; páginas de Figma consultadas; SCRUM-12 a SCRUM-21 consultadas. Mobbin rechazó la consulta porque exige plan de pago. No hay resultados de estudios con participantes.

## Operating Context
Ejecución local y preparación para un despliegue posterior, según respuesta del usuario. Destino de despliegue pendiente de definir.

## Product Principles
La integridad transaccional precede al diseño. Seleccionar no reserva. Cada mutación exige autorización en servidor. Los canales comparten reglas. La evidencia distingue ejecución de intención.

## Accessibility & Inclusion
WCAG 2.2 en el alcance del maestro; teclado, foco visible, contraste, mensajes y reflujo en 360, 768 y 1366 px. Respeto por movimiento reducido.
