# CU-08 · Reprogramar citas

**Responsable:** Lucas. **Actor:** Recepcionista / Administrador con permiso `citas.gestionar`. **Zona horaria:** America/La_Paz.

| Campo | Especificación |
| --- | --- |
| Caso de Uso | CU-08 Reprogramar cita |
| Propósito | Cambiar la reserva conservando la cita y su historial |
| Descripción | Seleccionar médico de la misma especialidad, fecha y turno disponible; confirmar el cambio |
| Actores / Actor Iniciador | Recepcionista o Administrador con permiso efectivo |
| Pre Condición | Sesión vigente, cita programada/confirmada y datos activos válidos |
| Flujo Principal | 1.- Abrir cita. 2.- Consultar nuevos turnos. 3.- Seleccionar turno. 4.- Confirmar. 5.- Revalidar y guardar atómicamente con auditoría. |
| Flujo Secundario | 2a.- Sin turnos: otra fecha/médico. 4a.- Datos inválidos: errores. 5a.- Versión antigua o conflicto: rollback; conservar reserva original. |
| Post Condición | Una sola cita con nuevo turno, mismo paciente/especialidad/estado y registro REPROGRAMAR |

## Objetivo y precondiciones

Cambiar el médico, la fecha o el turno de una cita programada o confirmada, conservando su código, paciente, especialidad y estado. Deben estar activos el paciente, la especialidad, el médico de destino y su vínculo con esa especialidad.

## Flujo principal

1. El actor abre «Reprogramar» desde el listado de citas.
2. Consulta la cita actual y elige médico de la misma especialidad y nueva fecha.
3. Consulta los turnos disponibles, expresados en hora de Bolivia.
4. Selecciona un turno diferente al actual y puede indicar un motivo de hasta 255 caracteres.
5. El servidor vuelve a comprobar permisos, versión de la cita, estado, actividad, horario y disponibilidad dentro de una transacción.
6. Actualiza médico, horario, inicio y fin; registra `REPROGRAMAR` con actor, motivo y valores anteriores/nuevos en `cambios_cita`.
7. Regresa al listado con confirmación.

## Alternativas y reglas

- Sin turnos: se invita a consultar otra fecha o médico; la cita conserva su reserva.
- Horario válido: día ISO 1–7, activo y vigente; el turno debe coincidir exactamente con su duración y caber completo dentro del horario.
- Se excluyen turnos pasados, bloqueos activos y cualquier cita del médico salvo las canceladas y la propia cita reprogramada. Las inasistencias permanecen ocupadas para este cálculo.
- Otra operación cambió la cita: se rechaza la versión antigua y se ofrece recargar.
- Otro usuario ocupó el turno: se rechaza el cambio; la transacción conserva íntegra la reserva anterior, también ante un conflicto `23P01` de PostgreSQL.
- Una cita cancelada, atendida/completada, en curso o con inasistencia no puede reprogramarse.
- El servidor ignora cambios enviados sobre paciente, especialidad, estado y fin; el fin se calcula desde el horario.
- El formulario permite consultar y seleccionar turnos sin JavaScript.

## Poscondición

La nueva reserva y su auditoría se guardan juntas, o no se guarda ninguna de las dos.
