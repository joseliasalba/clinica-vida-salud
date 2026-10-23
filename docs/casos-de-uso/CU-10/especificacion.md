# CU-10 — Confirmar cita, atención o inasistencia

Responsable: Lucas. Actor: administrador o recepción con `citas.gestionar`; médico con `agenda.propia`, limitado a citas asignadas a su registro de médico y cuenta de acceso.

El usuario abre Citas, selecciona «Atención / inasistencia», revisa paciente, especialidad, fecha y estado actual y selecciona el resultado. Puede añadir una observación de hasta 255 caracteres y debe marcar la confirmación explícita antes de guardar.

El servidor vuelve a comprobar sesión, permisos efectivos y propiedad del médico, bloquea la cita dentro de una transacción y compara la versión del formulario. Luego valida la transición y el horario, actualiza únicamente el estado y añade la auditoría a `cambios_cita` en la misma transacción. El resultado regresa al listado de citas con confirmación.

| Estado actual | Resultados permitidos en CU-10 |
| --- | --- |
| Programada | Confirmada, Atendida o Inasistencia |
| Confirmada | Atendida o Inasistencia |
| En curso | Atendida |
| Atendida, Completada, Cancelada o Inasistencia | Ninguno |

«Atendida» guarda el valor existente `completada`; no se añade un estado nuevo. La compatibilidad de lectura considera `atendida` terminal. Atención e inasistencia se permiten desde la hora de inicio, inclusive, con referencia `America/La_Paz`. Confirmar una cita futura es válido. Reprogramar y cancelar se resuelven mediante CU-08 y CU-09.

Un formulario antiguo, un resultado no permitido, un cierre anticipado o una confirmación faltante producen un error legible sin alterar cita ni auditoría. Una sesión ausente redirige al login; un permiso insuficiente devuelve 403 y una cita inexistente 404. Las citas terminales presentan sus datos sin botón para guardar. No se puede reabrir una cita desde este caso de uso.
