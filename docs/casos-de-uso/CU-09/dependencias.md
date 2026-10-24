# CU-09 — Dependencias

Las rutas de archivos son relativas a la raíz del repositorio.

| Capa | Archivo | Responsabilidad |
| --- | --- | --- |
| Rutas | `sistema/backend/routes/cancelacion.php` | GET y PATCH `/citas/{cita}/cancelar`; sesión y permiso |
| Controlador | `sistema/backend/app/Http/Controllers/Citas/CancelacionController.php` | Mostrar confirmación y coordinar respuesta |
| Validación | `sistema/backend/app/Http/Requests/Citas/CancelarCitaRequest.php` | Motivo, versión y aceptación explícita |
| Servicio | `sistema/backend/app/Services/Citas/CancelacionService.php` | Cancelación atómica, conservación de datos y auditoría |
| Reglas compartidas | `sistema/backend/app/Services/Citas/CitaWorkflow.php` | Autorización, bloqueo, transición y escritura de auditoría |
| Modelo compartido | `sistema/backend/app/Models/CitaMedica.php` | Relaciones, versión HMAC y snapshot |
| Interfaz | `sistema/frontend/views/citas/cancelar.blade.php` | Datos de la cita, motivo, confirmación, errores inline y CSRF |
| Pruebas | `sistema/backend/tests/Feature/Citas/CancelacionTest.php` | Casos HTTP y servicio con PostgreSQL aislado |

Se reutilizan `citas_medicas`, `cambios_cita`, `pacientes`, `medicos`, `especialidades`, `horarios_atencion` y la autorización existente por roles/permisos. No se requieren migraciones nuevas.

CU-07 provee la creación/reserva desde `/citas`; la gestión de citas existentes se encuentra en `/citas/gestion`. CU-08 comparte las reglas de versión y bloqueo al reprogramar. CU-10 comparte estados y auditoría. CU-12 permite consultar el estado de la cancelación sin exponer motivos privados; la trazabilidad queda en cambios_cita. Las cuentas por defecto autorizadas son administrador y recepción.
