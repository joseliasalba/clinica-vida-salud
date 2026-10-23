# CU-09 — Pruebas

Suite: `sistema/backend/tests/Feature/Citas/CancelacionTest.php`.

| Escenario | Resultado esperado |
| --- | --- |
| Visitante, médico o paciente | Login o 403, sin cambios |
| GET de confirmación | Datos de la cita y formulario; sin mutación ni auditoría |
| Administrador cancela cita programada | Estado cancelada, motivo normalizado y auditoría CANCELAR con actor/fecha/antes/después |
| Recepción cancela cita confirmada | Cancelación válida |
| Cita en curso, atendida, cancelada o con inasistencia | Rechazo; estado original conservado |
| Motivo vacío, array o demasiado largo; versión inválida; confirmación omitida/negada | Error de validación; sin cambios |
| Versión antigua o envío repetido | Rechazo, sin sobrescritura ni auditoría duplicada |
| Reserva posterior en el intervalo cancelado | PostgreSQL permite el nuevo registro; conserva la cita anterior |
| Permiso revocado | HTTP y servicio rechazan la operación |
| Token CSRF ausente | 419; sin cambios |
| HTML en nombres o motivo conservado | Contenido escapado |
| Identificador inexistente o no numérico | 404 |

**Integración 23/10/2026:** las 12 pruebas de cancelación pasaron dentro de la suite completa (183 pruebas / 2435 comprobaciones). Se corrigió la navegación de la prueba de formulario antiguo para seguir su redirección. Se verificó una cancelación con motivo en navegador y se conservó su cita en el historial. [Resultados y captura](../../pruebas/Lucas/RESULTADOS.md).

Desde `sistema/backend`, con la conexión de pruebas configurada al esquema aislado `clinica_testing`:

```text
php artisan test tests/Feature/Citas/CancelacionTest.php --compact
```

La suite usa transacciones para revertir sus datos ficticios. La prueba de disponibilidad valida la restricción PostgreSQL al insertar una nueva cita sobre el intervalo cancelado; no presenta como implementada la interfaz de reserva del CU-07.
