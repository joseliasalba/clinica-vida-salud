# CU-02 — Dependencias y trazabilidad

**Módulo:** Auth. **Ciclo:** 1. Las rutas siguientes parten de `sistema/`.

## Dependencias

| Dependencia | Uso | Estado |
| --- | --- | --- |
| CU-01 | Identidad y sesión del gestor | Implementado |
| Roles y permisos iniciales | Cuenta con `usuarios.gestionar` efectivo | Semilla disponible; no se crean cuentas o claves predeterminadas |
| PostgreSQL | Transacciones, unicidad y bloqueo de mutaciones administrativas | Disponible; pruebas en `clinica_testing` |
| Modelo de usuario | Hash de contraseña y comprobación de permisos | Se reutiliza `User` |
| CU-03/CU-04/CU-05 | Vincular cuentas con pacientes o médicos | Fuera del alcance de CU-02 |

## Requisito → código → prueba

| Requisito | Implementación | Prueba en `backend/tests/Feature/Auth/` |
| --- | --- | --- |
| Sesión y permiso requeridos | `backend/routes/usuarios.php`, `app/Http/Middleware/ProtectSession.php`, Gates | `UserAdministrationTest.php`: invitados y cuentas sin permiso |
| Búsqueda, filtro y paginación | `backend/app/Http/Controllers/Auth/UserController.php` | `UserAdministrationTest.php`: búsqueda, estado, paginación y salida escapada |
| Validación e identidad única | `backend/app/Http/Requests/Auth/SaveUserRequest.php`, `app/Rules/UniqueUserIdentity.php` | Duplicados sin distinguir mayúsculas y entradas inválidas |
| Creación y edición de cuentas | `backend/app/Services/Auth/UserAdministration.php` | Hash, varios roles, conservación de contraseña e historial de vínculos |
| Roles y permisos asignados | `RoleController.php`, `SaveRoleRequest.php`, `UserAdministration.php` | `RoleAdministrationTest.php`: altas, cambios, estados, vínculos y roles base |
| Estado global de permisos | `PermissionController.php`, `UserAdministration.php` | Desactivación y reactivación conservando asignaciones |
| Continuidad administrativa | Bloqueo y revalidación en `UserAdministration` | Rollback por pérdida de administración y `UserAdministrationConcurrencyTest.php` con dos procesos |
| Revocar sesiones y enlaces | `SessionController.php`, `ProtectSession.php`, `UserAdministration.php` | Desactivación/reactivación, cambio de contraseña y revocación de tokens |
| Pantallas | `frontend/views/auth/usuarios/`, `auth/roles/`, `auth/permisos/` | Pruebas HTTP de todas las pantallas |
| Estructura y navegación | `frontend/views/layouts/admin.blade.php`, `components/admin-nav.blade.php`, `views/inicio.blade.php` | Acceso desde inicio y navegación por roles/permisos |
| Presentación | `frontend/css/auth/usuarios.css`, estilos y JavaScript compartidos | Compilación correcta; inspección visual pendiente |

Los controladores y Form Requests abreviados en la tabla están bajo `backend/app/Http/Controllers/Auth/` y `backend/app/Http/Requests/Auth/`. Las pruebas comparten cuentas efímeras mediante `backend/tests/Support/CreatesAdministrativeUsers.php`; el proceso auxiliar está en `backend/tests/Fixtures/Auth/admin-mutation-worker.php`.

## Configuración y límites

`routes/usuarios.php` se carga desde `routes/web.php` dentro del grupo que desactiva caché de respuestas sensibles. Los gestores se determinan por permisos efectivos, no por comparar únicamente el nombre del rol. Las comprobaciones de permanencia administrativa cubren cambios hechos por CU-02; las modificaciones manuales en SQL y el vencimiento futuro de asignaciones requieren control operativo propio.

Volver a [especificación](especificacion.md) o consultar [pruebas](pruebas.md).
