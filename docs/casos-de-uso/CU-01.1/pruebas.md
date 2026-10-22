# CU-01.1 — Evidencia de pruebas

**Fecha:** 22/10/2026, horario de Bolivia. **Estado:** verificación automática aprobada; SMTP real y revisión manual pendientes.

## Ejecución observada

Desde `sistema/backend/`:

```powershell
php artisan test --compact
```

Resultado global: **33 pruebas aprobadas, 223 comprobaciones**, incluyendo otros módulos. Los detalles de la ejecución y sus límites generales también están en [las pruebas de CU-01](../CU-01/pruebas.md).

Para ejecutar solo la autenticación:

```powershell
php artisan test tests/Feature/Auth
```

## Cobertura relacionada

| Archivo en `sistema/backend/tests/Feature/Auth/` | Verificación |
| --- | --- |
| `PasswordRecoveryTest.php` | Mismo tipo de trabajo encolado para correos activos, inactivos e inexistentes |
| `PasswordRecoveryTest.php` | Envío simulado a cuenta activa, hash del token, URL, límite de reenvío y límite HTTP |
| `PasswordRecoveryTest.php` | Consulta del enlace sin consumirlo; expiración, alteración y reemplazo del token |
| `PasswordRecoveryTest.php` | Cambio de hash, rechazo de contraseña anterior, consumo y rechazo de reutilización |
| `PasswordRecoveryTest.php` | Cuenta inactiva no reactivada; reglas de contraseña y confirmación en servidor |
| `PasswordResetConcurrencyTest.php` | Dos procesos PHP y conexiones PostgreSQL: como máximo un cambio exitoso con el mismo token |
| `AuthenticationHttpTest.php` | Dos contextos de sesión con hash previo quedan rechazados después del cambio |

El proceso auxiliar reside en `sistema/backend/tests/Fixtures/Auth/reset-password-worker.php`. Usa únicamente el esquema de pruebas y no se ejecuta como parte de la aplicación.

## Pendientes

- Configurar SMTP fuera de Git y comprobar entrega real, enlace y aviso del cambio de contraseña.
- Comprobar el worker de la cola con el transporte real, sus reintentos y el manejo operativo de fallos de entrega.
- Revisar formularios, estados de error y uso sin JavaScript en navegador.
- Probar revocación con dos navegadores independientes. La evidencia automática usa contextos de sesión, no dos navegadores reales.
- Confirmar el alcance académico de recuperación en el primer ciclo.

No se enviaron correos externos como parte de esta verificación. Simular notificaciones no acredita entrega SMTP ni uniformidad temporal medida en producción.

Volver a [especificación](especificacion.md) o [dependencias](dependencias.md).
