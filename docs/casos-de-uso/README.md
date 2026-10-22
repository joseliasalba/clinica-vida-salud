# Catálogo de casos de uso

## Numeración acordada

El usuario confirmó el 22/10/2026 mantener la numeración original del proyecto. CU-03 corresponde al autorregistro de pacientes; CU-04, a la gestión de pacientes en recepción; CU-05, a médicos y especialidades; CU-06, a horarios; CU-07, a disponibilidad y reserva; y CU-11, a agenda médica. Esta decisión reemplaza el catálogo alternativo que se usó anteriormente en esta rama.

Los códigos CU son propios del proyecto y no sustituyen los RF del enunciado. Cada caso tiene una carpeta directa `CU-XX/` con `especificacion.md`, `dependencias.md` y `pruebas.md`; se usa `PLANTILLA.md` para la especificación. Los ciclos se indican aquí, sin anidar casos dentro de otros casos ni de carpetas de entregas.

| Código | Caso de uso | Actor principal | Fuente | Ciclo propuesto |
| --- | --- | --- | --- | --- |
| CU-01 | Iniciar y cerrar sesión | Usuario | RF01 | 1 |
| CU-02 | Administrar usuarios, roles y permisos | Administrador | RF01 y roles | 1 |
| CU-03 | Registrarse como paciente | Paciente | Perfil paciente y decisión del equipo | 1 |
| CU-04 | Registrar y gestionar pacientes en recepción | Recepción / administrador autorizado | RF02 | 1 |
| CU-05 | Gestionar médicos y especialidades | Administrador | RF03, RF04 | 1 |
| CU-06 | Configurar horarios de atención | Administrador | RF05 | 1 |
| CU-07 | Consultar disponibilidad y reservar cita | Paciente / recepción | RF06, RF07 | 1 |
| CU-08 | Reprogramar una cita | Recepción; alcance del paciente por definir | RF08 | 1 |
| CU-09 | Cancelar una cita | Recepción; alcance del paciente por definir | RF09 | 1 |
| CU-10 | Confirmar y registrar atención o inasistencia | Recepción / médico según permiso | RF10 | 1 |
| CU-11 | Consultar agenda médica | Médico / recepción según permiso | RF11 | 1 |
| CU-12 | Consultar historial de citas | Paciente / personal autorizado | RF12 | 1 |
| CU-13 | Generar reportes administrativos | Administrador / personal autorizado | RF13 | 2 |
| CU-14 | Actualizar perfil, salud declarada y contacto de emergencia | Paciente | Perfil paciente | 2 |
| CU-15 | Registrar consulta y tratamiento | Médico autorizado | Contexto y perfil clínico | 2 |
| CU-16 | Solicitar estudios y consultar/publicar resultados | Médico / paciente; publicador por definir | Contexto de continuidad | 2 |
| CU-17 | Gestionar preferencias y recordatorios | Paciente / proceso programado | Perfil paciente | 2 |

La asignación por ciclo es propuesta, porque el plan detallado del ZIP no se ha podido leer. El bloqueo de citas duplicadas y la autorización son reglas transversales, no pasos opcionales de la interfaz.

Orden sugerido para comenzar: CU-01, CU-03/CU-04, CU-05, CU-06 y CU-07. El caso CU-07 debe incluir reservas simultáneas en sus criterios de aceptación.

## Documentación y estado

| Caso | Módulo | Especificación | Dependencias y código | Evidencia | Estado |
| --- | --- | --- | --- | --- | --- |
| CU-01 | Auth | [Especificación](CU-01/especificacion.md) | [Trazabilidad](CU-01/dependencias.md) | [Pruebas](CU-01/pruebas.md) | Implementado con pruebas automáticas; revisión en navegador pendiente |
| CU-01.1 | Auth | [Especificación](CU-01.1/especificacion.md) | [Trazabilidad](CU-01.1/dependencias.md) | [Pruebas](CU-01.1/pruebas.md) | Implementado con pruebas automáticas; SMTP real y revisión en navegador pendientes |
| CU-02 | Auth | [Especificación](CU-02/especificacion.md) | [Trazabilidad](CU-02/dependencias.md) | [Pruebas](CU-02/pruebas.md) | Usuarios, roles y permisos implementados y probados; revisión visual pendiente |
| CU-04 | Pacientes | [Especificación](CU-04/especificacion.md) | [Código y dependencias](CU-04/dependencias.md) | [Pruebas](CU-04/pruebas.md) | Fichas administrativas implementadas y probadas; revisión visual pendiente |
| CU-05 | Médicos y especialidades | [Especificación](CU-05/especificacion.md) | [Trazabilidad](CU-05/dependencias.md) | [Pruebas](CU-05/pruebas.md) | Especialidades implementadas; gestión de médicos pendiente |

La tabla de estado describe únicamente el código presente en esta rama. El autorregistro CU-03 desarrollado en la copia local principal todavía no está integrado aquí. Los demás casos no incluidos están pendientes de especificación e implementación de sus flujos. Tener tablas o permisos iniciales no significa que esos casos estén terminados.

## Convenciones

- **DEFINIDO:** requisito con fuente identificada o decisión expresamente acordada; indicar cuál de las dos es.
- **PROPUESTO:** decisión aún no acordada.
- **PENDIENTE:** requisito por aclarar, código por implementar o verificación por realizar.

La [guía de estructura](../estructura.md) explica dónde colocar controladores, validaciones, modelos, vistas, recursos y pruebas de cada módulo. Las verificaciones pendientes se registran en el documento de pruebas de cada caso, sin presentarlas como aprobadas.
