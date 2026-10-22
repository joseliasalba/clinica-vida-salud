# CU-01.1 — Dependencias y trazabilidad

**Módulo:** Auth. **Ciclo propuesto:** 1, como extensión de CU-01; obligatoriedad académica pendiente de confirmar.

## Dependencias

| Dependencia | Uso | Estado |
| --- | --- | --- |
| Usuario activo con correo | Identificar al titular sin reactivar cuentas | Disponible |
| `password_reset_tokens` | Token almacenado como hash, vencimiento y consumo | Disponible; broker configurado con quince minutos |
| Cola `database` y tabla `jobs` | Respuesta pública uniforme sin esperar al correo | Disponible; requiere worker activo |
| Transporte de correo | Entrega del enlace y aviso del cambio | Configuración de desarrollo; SMTP real no acreditado |
| CU-01 y `ProtectSession` | Invalidar acceso previo después de cambiar el hash | Disponible; comprobación en la siguiente petición protegida |

## Requisito → implementación → prueba

| Comportamiento | Implementación bajo `sistema/` | Prueba bajo `backend/tests/` |
| --- | --- | --- |
| Solicitud genérica y limitada | `backend/routes/auth.php`, `Controllers/Auth/PasswordController.php`, limitadores en `AppServiceProvider.php` | `Feature/Auth/PasswordRecoveryTest.php`: cuentas conocidas, desconocidas e inactivas; límites |
| Envío asíncrono | `backend/app/Jobs/SendPasswordResetLink.php` | Trabajo encolado para cualquier correo; envío simulado solo a cuentas activas |
| Enlace y notificación | Broker Laravel, `backend/config/auth.php`, notificación configurada en `AppServiceProvider.php` | Hash almacenado, URL con correo y token, vencimiento y reemplazo |
| Clave y confirmación | `backend/app/Http/Requests/Auth/ResetPasswordRequest.php`, `app/Rules/PasswordPolicy.php` | Claves cortas, comunes, repetitivas, demasiado largas y confirmación incorrecta |
| Cambio y uso único | `PasswordController::reset`, transacción y bloqueo del usuario | Token consumido y rechazo del segundo uso; dos procesos en `PasswordResetConcurrencyTest.php` |
| Revocación de sesiones | Nuevo hash, rotación de `remember_token`, `ProtectSession` | `AuthenticationHttpTest.php`: dos contextos de sesión con hash anterior |
| Aviso de cambio | `backend/app/Notifications/PasswordChanged.php` | Notificaciones simuladas; entrega externa pendiente |
| Pantallas y recursos | `frontend/views/auth/`, `views/components/`, `css/auth/`, `js/auth/` | Respuestas HTTP y compilación; revisión visual pendiente |

Los controladores de la tabla están dentro de `backend/app/Http/`. El proceso auxiliar de concurrencia está en `backend/tests/Fixtures/Auth/reset-password-worker.php` y solo admite el entorno de pruebas.

## Ejecución local

Desde `sistema/backend/`, con el entorno activado:

```powershell
php artisan queue:work database --tries=3 --timeout=60
```

`composer run dev` también inicia un worker de la cola `database`. El transporte `log` de desarrollo no demuestra entrega real y no debe usarse para correos con tokens en producción. No se incluyen credenciales en la documentación.

Volver a la [especificación](especificacion.md) o consultar [las pruebas](pruebas.md).
