# CU-01 — Dependencias y trazabilidad

**Módulo:** Auth. **Ciclo:** 1 según el catálogo. **Fuente:** RF01 y decisiones de diseño acordadas; las referencias RN12/RN14 del texto sugerido siguen sin contrastar.

## Dependencias

| Dependencia | Uso | Estado |
| --- | --- | --- |
| PostgreSQL y migraciones | Usuarios, roles, permisos y asignaciones vigentes | Disponible |
| Modelo `User` y proveedor `usuarios_activos` | Verificar el hash y excluir cuentas desactivadas | Disponible |
| Sesiones y caché Laravel | Mantener acceso y limitar intentos | Disponible |
| Catálogo de roles y permisos | Mostrar únicamente funciones autorizadas | Semilla disponible; la administración corresponde a CU-02 |
| Cuenta activa | Precondición para autenticarse | Creación local con `php artisan clinica:crear-admin`; no hay contraseña predeterminada |
| CU-01.1 | Recuperar acceso cuando se pierde la contraseña | Extensión opcional para el login; [ver dependencias](../CU-01.1/dependencias.md) |

## Requisito → implementación → prueba

Las rutas de archivos parten de la raíz del repositorio.

| Comportamiento | Implementación | Evidencia automática |
| --- | --- | --- |
| Identificar por usuario o correo | `backend/app/Http/Controllers/Auth/SessionController.php`, `Requests/Auth/LoginRequest.php` | `Feature/Auth/AuthenticationHttpTest.php`: login por ambos identificadores y rechazo de ambigüedad |
| Verificar cuenta activa y contraseña | `backend/app/Models/User.php`, `app/Providers/AppServiceProvider.php` | `AuthenticationFoundationTest.php` y `AuthenticationHttpTest.php` |
| Limitar intentos | `config/seguridad.php`, `LoginRequest.php`, `SessionController.php` | Cinco fallos, espera y recuperación tras vencer el límite |
| Proteger y cerrar sesión | `app/Http/Middleware/ProtectSession.php`, `SessionController.php`, prioridad en `bootstrap/app.php` | Expiración, desactivación/reactivación, logout y cambio de hash |
| Autorización por permisos | Gates en `AppServiceProvider.php`, `User::tienePermiso`, `frontend/views/inicio.blade.php` | `RolesPermisosTest.php` y pruebas HTTP de cuatro roles y varios roles |
| CSRF, validación y escape | Middleware web, Form Requests, Blade | CSRF habilitado en prueba, contraseña ausente en datos repoblados y salida escapada |
| Rutas y pantallas | `backend/routes/auth.php`, `routes/web.php`, `frontend/views/auth/`, `views/layouts/` | Acceso público, redirecciones y contenido de pantallas |
| Mensajes comunes | `frontend/views/components/messages.blade.php` | Renderizado mediante `<x-messages />` en pruebas HTTP |

En la tabla, `backend/` y `frontend/` están bajo `sistema/`. Los archivos de prueba están bajo `sistema/backend/tests/`.

## Límites de alcance

Los módulos clínicos del inicio son avisos de disponibilidad futura, no funcionalidades clínicas implementadas. La cuenta administradora se crea en una terminal con contraseña oculta; las pruebas generan sus propias cuentas aisladas. La revisión visual y de navegación en navegador permanece pendiente.

Volver a la [especificación](especificacion.md) o consultar [las pruebas](pruebas.md).
