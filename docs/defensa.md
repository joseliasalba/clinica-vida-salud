# Preparación de defensa

## CU-08 — Reprogramar citas — Lucas

1. **¿Qué datos cambian?** Médico de la misma especialidad, horario e inicio/fin. Conservamos código, paciente, especialidad y estado.
2. **¿Por qué consultar disponibilidad otra vez al guardar?** La pantalla puede quedar antigua; otra persona puede ocupar el turno antes de confirmar.
3. **¿Qué ocurre si se ocupa el turno?** PostgreSQL rechaza el solape y se revierte toda la transacción, manteniendo la reserva original.
4. **¿Qué evita dos ediciones incompatibles?** Bloqueamos los médicos en orden estable y la cita, y comparamos la versión firmada antes de actualizar.
5. **¿Es la misma función que registrar una cita?** Comparte reglas de disponibilidad, pero opera sobre una cita existente y excluye su propio intervalo al consultar; crear una reserva corresponde a CU-07.

## CU-09 — Cancelar citas — Lucas

1. **¿Se borra la cita?** No. Cambia a cancelada y conserva su identidad, fechas, relaciones e historial.
2. **¿Por qué exigimos motivo y confirmación?** Permiten revisar la operación y explicar el cambio en la auditoría.
3. **¿Qué estados pueden cancelarse?** Programada y confirmada. Una atención terminada no admite cancelación directa.
4. **¿Cómo se libera el turno?** La disponibilidad excluye canceladas de la ocupación; el registro permanece para consultas históricas.
5. **¿Qué pasa con un envío repetido?** La versión ya cambió y se rechaza; no se duplica el evento ni se reemplaza el motivo guardado.

## CU-10 — Confirmar atención o inasistencia — Lucas

1. **¿Dónde están las transiciones?** En `CitaWorkflow::TRANSITIONS`, compartido por los módulos de modificación.
2. **¿Puede registrarse inasistencia antes de la cita?** No. El servidor exige que haya llegado el inicio en la zona horaria de Bolivia.
3. **¿Por qué aparece `completada` en la base?** Es el valor histórico existente; la interfaz lo presenta como Atendida para conservar compatibilidad.
4. **¿Puede un médico cambiar una cita ajena?** No. Además del permiso comprobamos que la cita esté asignada a su perfil de médico.
5. **¿Qué prueba la auditoría?** Guarda actor, acción, fecha y datos anteriores/nuevos en la misma transacción que cambia el estado.

## CU-12 — Consultar historial — Lucas

1. **¿Qué contiene el historial?** Código, fecha/hora, médico, especialidad, estado y fecha de registro; incluye canceladas y citas pasadas.
2. **¿Cómo impedimos ver otro paciente?** La cuenta paciente se vincula por `pacientes.usuario_id`; un ID ajeno se rechaza en servidor.
3. **¿Qué ocurre con una ficha inactiva?** El personal autorizado conserva acceso a sus citas anteriores.
4. **¿Se muestran motivos o diagnósticos?** No. La consulta selecciona solo campos administrativos y no expone esos datos.
5. **¿Por qué usar filtros y paginación?** Permiten revisar períodos o estados sin cargar todo el historial; se conservan al cambiar de página.

## CU-05 — Gestionar médicos y especialidades: especialidades

1. **¿Por qué guardamos nombre y nombre normalizado?** El nombre conserva la presentación elegida; la clave normalizada permite comparar sin diferencias de tildes, mayúsculas o espacios. PostgreSQL tiene un UNIQUE sobre esa clave.
2. **¿Qué pasa si dos administradores registran el mismo nombre al mismo tiempo?** La validación inicial puede aprobar ambos formularios, pero la restricción única solo permite uno. La otra transacción se revierte y recibe un mensaje comprensible.
3. **¿Por qué desactivamos en lugar de eliminar?** La especialidad puede estar vinculada a médicos y citas. Cambiar `activo` conserva esas referencias y permite reactivarla.
4. **¿Ocultar el botón es suficiente para proteger la operación?** No. Todas las rutas exigen sesión y permiso efectivo; las escrituras también verifican el permiso en el servicio y requieren CSRF.
5. **¿Cómo evitamos que una edición antigua reemplace otra reciente?** El formulario incluye una firma de la versión leída. Dentro de una transacción bloqueamos la fila y comparamos esa firma antes de actualizar; si cambió, pedimos recargar.

La continuidad con Laravel fue confirmada por el usuario. La numeración acordada es CU-02 para usuarios/roles/permisos, CU-03 para autorregistro, CU-04 para pacientes en recepción, CU-05 para médicos y especialidades, CU-06 para horarios, CU-07 para disponibilidad y reserva, y CU-11 para agenda médica. Esta defensa cubre el submódulo de especialidades; la gestión de médicos sigue pendiente en esta rama.
