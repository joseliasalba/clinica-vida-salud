# Dependencias de CU-08

- Autenticación, protección de sesión y permiso existente `citas.gestionar`.
- Catálogos activos de pacientes, médicos, especialidades y `medico_especialidad`.
- Tablas existentes `horarios_atencion`, `indisponibilidades_medico`, `citas_medicas` y `cambios_cita`; sin migraciones nuevas.
- Infraestructura compartida: modelo `CitaMedica`, `CitaWorkflow`, listado de citas y layout `layouts.citas`.
- `ReprogramacionDisponibilidadService` calcula los turnos exclusivamente para reprogramar. CU-07 de Elías puede reutilizar o consolidar este cálculo al integrar su consulta/reserva inicial.
- Bloqueo de médicos por ID ascendente antes de bloquear la cita; versión de edición y restricción PostgreSQL contra solapamientos.
- La base conserva valores históricos de estado en minúsculas (`programada`, `confirmada`, etc.).

## Rutas

| Método | Ruta | Nombre |
|---|---|---|
| GET | `/citas/{cita}/reprogramar` | `citas.reprogramar.edit` |
| GET | `/citas/{cita}/reprogramar/turnos?medico_id=…&fecha=AAAA-MM-DD` | `citas.reprogramar.turnos` |
| PUT | `/citas/{cita}/reprogramar` | `citas.reprogramar.update` |

El endpoint de turnos devuelve `turnos` con `horario_id`, `inicio`, `fin` y `duracion_minutos`. El PUT admite `medico_id`, `fecha`, `horario_id`, `inicio` (`HH:mm`), `version` y `motivo` opcional. El formulario sin JavaScript envía `turno=horario_id|HH:mm`, que el FormRequest convierte a esos campos.
