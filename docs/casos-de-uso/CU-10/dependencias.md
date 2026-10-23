# Dependencias de CU-10

- Autenticación y protección de sesión existentes, con permisos `citas.gestionar` y `agenda.propia` vigentes al enviar.
- Modelos compartidos `CitaMedica`, `Paciente`, `Medico` y `Especialidad`; tablas existentes `citas_medicas`, `cambios_cita`, `medicos`, catálogos y relaciones de roles/permisos.
- `CitaWorkflow` mantiene la fuente común de transiciones, comprobación de propiedad, bloqueo con versión y auditoría. `AsistenciaService` ejecuta la actualización y auditoría en una sola transacción.
- `GET /citas/{cita}/asistencia` abre la revisión (`citas.asistencia.edit`); `PATCH` al mismo recurso confirma (`citas.asistencia.update`). La ruta se incorpora desde `routes/asistencia.php`, bajo el middleware de sesión existente.
- Vista `citas.asistencia` sobre `layouts.citas`, con controles nativos, etiquetas, errores, CSRF y estilos administrativos compartidos. No requiere dependencias ni migraciones nuevas.
- Listado compartido `citas.index` para acceso y retorno. El historial de CU-12 permite consultar la acción `CAMBIAR_ESTADO`, estado anterior, estado nuevo, observación, actor y fecha.

Los formularios envían `estado`, `version`, `confirmacion` y una observación `motivo` opcional. Los identificadores del paciente/médico, fechas y demás campos de la cita quedan fuera de los datos editables.
