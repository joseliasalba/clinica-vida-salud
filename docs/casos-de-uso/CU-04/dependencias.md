# CU-04 — Dependencias y trazabilidad del plan

**Estado:** Implementado. Las rutas de código parten de `sistema/`. La tabla de archivos previstos se conserva como referencia del plan; el mapa siguiente identifica el código disponible.

## Código implementado

| Responsabilidad | Ruta |
| --- | --- |
| Entidad y firma de edición | `backend/app/Models/Paciente.php` |
| Registro/edición transaccional, código, bloqueo y auditoría | `backend/app/Services/Pacientes/PatientRegistration.php` |
| Validación, normalización y duplicados | `backend/app/Http/Requests/Pacientes/SavePatientRequest.php` |
| Controlador | `backend/app/Http/Controllers/Pacientes/PacienteController.php` |
| Rutas y acceso | `backend/routes/pacientes.php`, cargado desde `routes/web.php` |
| Índice único de CI normalizado | `backend/database/migrations/2026_10_22_160330_add_normalized_ci_index_to_pacientes_table.php` |
| Factory | `backend/database/factories/PacienteFactory.php` |
| Pruebas | `backend/tests/Feature/Pacientes/PatientManagementTest.php` |
| Pantallas | `frontend/views/pacientes/index.blade.php`, `form.blade.php`, `show.blade.php` |
| Layout del módulo | `frontend/views/layouts/pacientes.blade.php` |
| Estilos y entrada | `frontend/css/pacientes/pacientes.css`, importado desde `css/app.css` |
| Enlace de inicio | `frontend/views/inicio.blade.php` |

Se utiliza la validación de CI del Form Request y el índice de PostgreSQL; no se añadió una regla separada que duplicara esa lógica. El servicio coordina la creación y el código dentro de una transacción y protege las ediciones simultáneas.

## Base existente revisada

| Pieza | Evidencia y uso |
| --- | --- |
| Identidad y sesión | CU-01, `backend/app/Http/Middleware/ProtectSession.php` y autenticación existente |
| Autorización | `backend/config/permisos.php` define `pacientes.gestionar`; `AppServiceProvider` registra Gates; `User::tienePermiso()` comprueba permisos efectivos |
| Tabla de pacientes | `backend/database/migrations/2026_10_21_200007_create_pacientes_table.php`: ID, cuenta opcional única, código único, CI nullable único, datos administrativos y estado |
| Referencias al paciente | Contactos de emergencia, perfiles declarados, historias clínicas, preferencias, citas y enfermedades; conservar claves y no borrar fichas |
| Navegación | `frontend/views/inicio.blade.php` muestra ahora el enlace «Gestionar pacientes» según permiso |
| Pruebas | PostgreSQL aislado en `clinica_testing`, preparado por `backend/tests/TestCase.php`; helpers de cuentas y roles disponibles |
| Presentación | `frontend/views/layouts/app.blade.php`, `components/messages.blade.php` y estilos compartidos |

Antes de implementar se consultaron únicamente agregados de la base local: cero pacientes, cero colisiones normalizadas de CI y cero códigos `PAC-` reservados. La migración del índice se aplicó correctamente el 22 de octubre de 2026, sin modificar fichas existentes.

## Requisito → archivo propuesto → verificación prevista

| Necesidad | Archivo propuesto o existente a modificar | Prueba prevista |
| --- | --- | --- |
| Entidad y código | `backend/app/Models/Paciente.php` | Código único, inmutable y sin aceptar campos externos |
| Datos efímeros de prueba | `backend/database/factories/PacienteFactory.php` | Fixtures aislados; sin sembrar pacientes en la base de trabajo |
| Validación | `backend/app/Http/Requests/Pacientes/SavePatientRequest.php` | CI, campos obligatorios, fechas, tipos y longitud |
| Identidad única | Regla en `backend/app/Rules/` si aporta reutilización; migración adicional solo si el análisis lo requiere | CI normalizado y dos altas concurrentes |
| Flujos HTTP | `backend/app/Http/Controllers/Pacientes/PacienteController.php` | Listado, alta, consulta, edición y estado |
| Rutas y permisos | `backend/routes/pacientes.php`, cargado desde `backend/routes/web.php` | Invitado, 403, CSRF, rutas directas y ausencia de borrado |
| Acceso desde inicio | `frontend/views/inicio.blade.php` | Enlace visible para el permiso efectivo |
| Pantallas | `frontend/views/pacientes/index.blade.php`, `form.blade.php`, `show.blade.php` | Respuestas HTTP, validaciones y salida escapada |
| Presentación del módulo | Layout/componente según reutilización real; `frontend/css/pacientes/` si requiere estilos propios | Navegación, teclado, móvil y compilación |
| Cobertura | `backend/tests/Feature/Pacientes/PatientManagementTest.php`; prueba concurrente separada si hace falta | Matriz de `pruebas.md` |

No crear una capa DAO ni un servicio que solo replique Eloquent. Un servicio tendrá sentido si la generación del código, transacciones o futura vinculación requieren coordinar varias operaciones.

## Dependencias que no bloquean la ficha administrativa propuesta

- CU-03 no necesita estar terminado para registrar un paciente sin cuenta, si se acuerda ese alcance.
- CU-05, CU-06 y CU-07 usarán pacientes después; registrar un paciente no reserva una cita.
- CU-14 y CU-15 tratarán información declarada y clínica; recepción no obtiene acceso a ella por este plan.
- La creación de una cuenta por CU-02 no debe convertirse automáticamente en una vinculación de paciente por coincidencia de correo.
