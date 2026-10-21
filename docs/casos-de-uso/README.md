# Catálogo inicial de casos de uso

Propuesta de organización para comenzar el análisis. Los códigos CU son nuevos y no sustituyen los RF del enunciado. Cada caso debe especificarse usando `PLANTILLA.md` antes de considerarse terminado.

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
