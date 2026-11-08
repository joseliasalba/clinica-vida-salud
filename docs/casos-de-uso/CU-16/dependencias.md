# CU-16 — Trazabilidad

| Elemento | Implementación |
| --- | --- |
| Entrada | GET `/estudios`, `/estudios/solicitar`, `/estudios/{estudio}` y `/estudios/{estudio}/archivo`; POST `/estudios`; PUT `/estudios/{estudio}/resultado`; PATCH `/estudios/{estudio}/estado` |
| Lógica | [EstudioController y StudyManagement](../../../sistema/backend/app/Services/Estudios/StudyManagement.php) |
| Interfaz | [Vista principal](../../../sistema/frontend/views/estudios/show.blade.php) |
| Datos del dominio | Consulta médica, SolicitudEstudio, ResultadoEstudio, Evento de estudio |
| Prueba del flujo | [LucasClinicalWorkflowTest](../../../sistema/backend/tests/Feature/LucasClinicalWorkflowTest.php) |
| Regresión de estudios | [StudyManagementTest](../../../sistema/backend/tests/Feature/Estudios/StudyManagementTest.php) |

Los permisos iniciales se asignan con `php artisan db:seed --class=RolesPermisosSeeder --no-interaction`. Los CU-13 a CU-15 usan las tablas existentes. CU-16 usa las migraciones de estudios de la base de main. Su enlace se ofrece en el detalle de la consulta guardada del CU-15.

[Diagramas editables](../../diagramas/CU-16.drawio)
