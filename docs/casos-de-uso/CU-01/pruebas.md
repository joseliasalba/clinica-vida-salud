# CU-01 — Evidencia de pruebas

**Fecha:** 22/10/2026, horario de Bolivia. **Estado:** verificación automática aprobada; pruebas manuales indicadas abajo pendientes.

## Ejecución observada

Desde `sistema/backend/`, con el entorno activado:

```powershell
php artisan test --compact
```

Resultado de la suite completa del proyecto: **33 pruebas aprobadas, 223 comprobaciones**. Incluye Auth, restricciones de citas y pruebas básicas; no son 33 pruebas exclusivas de CU-01.

También se ejecutaron correctamente la compilación de Vite, la compilación de vistas Blade y el listado de las nueve rutas web de la aplicación. No se crearon cuentas reales ni se modificó el esquema de desarrollo durante esta reorganización.

## Cobertura relacionada

| Prueba en `sistema/backend/tests/Feature/Auth/` | Verificación |
| --- | --- |
| `AuthenticationFoundationTest.php` | Hash, usuario clínico, rechazo de inactivos y configuración de PostgreSQL |
| `AuthenticationHttpTest.php` | Login por usuario/correo, regeneración de sesión, logout POST, validación, mensajes genéricos y limitación de intentos |
| `AuthenticationHttpTest.php` | Expiración, desactivación y posterior reactivación sin recuperar la sesión anterior |
| `AuthenticationHttpTest.php` | Menú de cuatro roles, combinación de roles, rechazo por permiso y escape de salida |
| `AuthenticationHttpTest.php` | CSRF con el bypass de pruebas explícitamente deshabilitado y rechazo de redirección externa |
| `RolesPermisosTest.php` | Semilla repetible, asignaciones expiradas o futuras y desactivación de roles, vínculos y permisos |

## Corrección encontrada al verificar

La suite inicial falló al volver a activar una cuenta: la sesión anterior podía recuperar acceso porque el middleware de autenticación redirigía antes de que `ProtectSession` la invalidara. Se registró su prioridad antes de `AuthenticatesRequests` en `bootstrap/app.php`. La prueba existente pasó después de la corrección y la suite completa quedó aprobada.

## Pendientes

- Revisión visual en escritorio y móvil, teclado y lector de pantalla.
- Recorrido real sin JavaScript; las pruebas HTTP verifican el servidor, no un navegador con JavaScript deshabilitado.
- Cookies y comportamiento de volver atrás tras logout en un navegador real; las respuestas protegidas declaran `no-store`.
- Prueba de creación de una cuenta administradora real con contraseña elegida por su titular y oculta en terminal. El comando sí está cubierto por una prueba automatizada.
- Contraste final con el enunciado y aceptación del equipo o docente.

Volver a [especificación](especificacion.md) o [dependencias](dependencias.md).
