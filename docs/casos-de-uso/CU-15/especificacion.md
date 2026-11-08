# CU-15 — Registrar consulta y tratamiento

**Ciclo:** 2. **Responsable de la entrega:** Lucas. **Fuente:** Sistema Web de Gestión de Citas Médicas, catálogo y planificación del ciclo 2 (páginas 43–47 del PDF).

| Campo | Especificación |
| --- | --- |
| Objetivo | Registrar la atención y, cuando corresponda, el tratamiento de una cita propia. |
| Actor principal | Médico asignado a la cita |
| Actores secundarios | No intervienen en el flujo principal. |
| Precondiciones | Sesión vigente, permiso `consultas.registrar`, médico activo vinculado y cita propia en atención o atendida cuyo inicio ya llegó. |
| Evento inicial | Abrir una cita desde «Consultas médicas». |
| Resultado de éxito | Queda una consulta asociada a la cita y a la historia del paciente, con su tratamiento opcional. La cita no cambia de estado. |
| Garantía ante fallo | La petición rechazada no modifica los datos de la operación. |

## Flujo principal

| Paso | Acción del actor | Respuesta del sistema |
| --- | --- | --- |
| 1 | Abre Consultas médicas | Lista únicamente sus citas elegibles. |
| 2 | Selecciona una cita | Comprueba que la cita pertenece al médico y muestra el formulario o el registro existente. |
| 3 | Completa la evaluación, los datos clínicos y el tratamiento opcional | Valida textos, rangos y fechas. |
| 4 | Registra la consulta | Bloquea la cita, impide duplicados, reutiliza o abre la historia clínica y guarda consulta y tratamiento juntos. |
| 5 | Revisa la consulta guardada | Muestra el detalle y, con permiso, la opción «Solicitar estudio» del CU-16. |

## Flujos alternativos y excepciones

| Código | Paso | Situación | Respuesta |
| --- | --- | --- | --- |
| A1 | 2 | Ya existe una consulta | Muestra el registro en modo de lectura. |
| E1 | 4 | La cita es ajena, futura o no elegible | No permite abrirla ni guardar una consulta. |
| E2 | 4 | Otro envío ya registró la consulta | Devuelve un error de duplicado y mantiene el registro anterior. |

## Reglas de negocio y permisos

- No se crea una consulta desde una cita cancelada, con inasistencia, futura o perteneciente a otro médico.
- La evaluación es obligatoria; el tratamiento es opcional. Si se informan fechas del tratamiento, se exigen indicaciones y se valida el orden.
- La consulta se crea una vez por cita. El detalle no ofrece edición.

## Datos utilizados

Cita, paciente, médico, historia clínica, fecha de atención, evaluación, datos clínicos, indicaciones y fechas de tratamiento.

## Criterio de aceptación

- Dada una cita elegible, el médico asignado guarda una sola consulta vinculada al paciente y puede continuar a la solicitud de un estudio.

## Evidencia y pendientes

Ver [trazabilidad](dependencias.md), [pruebas](pruebas.md) y [diagramas editables](../../diagramas/CU-15.drawio).

Revisión visual autenticada y comprobación de los campos opcionales en navegador.
