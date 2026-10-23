# CU-09 — Cancelar citas

| Campo | Especificación |
| --- | --- |
| Propósito | Cancelar una cita conservando sus datos y liberar su horario para una nueva reserva |
| Responsable | Lucas, según distribución acordada el 23/10/2026 |
| Actor | Administrador o recepción con permiso efectivo `citas.gestionar` |
| Precondiciones | Sesión activa y cita existente en estado `programada` o `confirmada` |
| Flujo principal | Abrir Citas, seleccionar Cancelar, revisar paciente/médico/especialidad/fecha, indicar motivo, marcar la confirmación y enviar. El servidor valida los datos, bloquea la cita, verifica su versión y estado y registra la cancelación junto a su auditoría en una transacción. Vuelve al listado con confirmación. |
| Alternativas | Volver sin cancelar no cambia datos. Datos inválidos muestran errores y conservan el motivo. Sin sesión se redirige al ingreso; sin permiso se devuelve 403; CSRF inválido, 419; identificador inexistente, 404. Abrir cancelación de cita terminal o en curso devuelve 409. Un formulario antiguo se rechaza con error de versión. |
| Postcondición | La cita conserva ID, paciente, médico, especialidad, horario y fecha; cambia a `cancelada`, guarda `motivo_cancelacion` y crea un registro `CANCELAR` en `cambios_cita`. |

## Reglas implementadas

- Solo se permite cancelar citas programadas o confirmadas. Citas en atención, atendidas, canceladas o con inasistencia no admiten la operación.
- El motivo es obligatorio, de texto, de hasta 255 caracteres. Se recortan y compactan espacios antes de validar.
- Se requiere confirmación explícita en servidor. La pantalla funciona sin JavaScript.
- La versión HMAC y el bloqueo de fila rechazan formularios antiguos y envíos repetidos. La autorización se vuelve a comprobar dentro del servicio.
- Cancelar actualiza el registro, sin DELETE. El historial guarda actor, fecha, motivo y datos anteriores/nuevos en la misma transacción.
- La exclusión PostgreSQL existente ignora filas `cancelada`; una reserva posterior puede utilizar ese intervalo.
- Los campos de identidad, fechas y estado enviados adicionalmente por el cliente no se asignan. Blade escapa el contenido mostrado.

## Criterios de aceptación

1. Administrador y recepción autorizados pueden cancelar y consultar la confirmación; otros roles y visitantes no.
2. Motivo, versión y confirmación inválidos impiden cualquier cambio.
3. La cancelación conserva las relaciones y deja trazabilidad completa.
4. Una petición antigua o repetida no sobrescribe datos ni duplica el historial.
5. El intervalo cancelado vuelve a ser admisible por PostgreSQL.

Consultar [dependencias](dependencias.md) y [pruebas](pruebas.md).
