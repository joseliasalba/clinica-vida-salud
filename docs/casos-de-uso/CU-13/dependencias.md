# CU-13 — Trazabilidad

| Elemento | Implementación |
| --- | --- |
| Entrada | GET `/reportes` |
| Lógica | [ReporteController](../../../sistema/backend/app/Http/Controllers/Reportes/ReporteController.php) |
| Interfaz | [Vista principal](../../../sistema/frontend/views/reportes/index.blade.php) |
| Datos del dominio | CitaMedica, Paciente, Medico, Especialidad |
| Prueba del flujo | [LucasClinicalWorkflowTest](../../../sistema/backend/tests/Feature/LucasClinicalWorkflowTest.php) |


Los permisos iniciales se asignan con `php artisan db:seed --class=RolesPermisosSeeder --no-interaction`. Los CU-13 a CU-15 usan las tablas existentes.

[Diagramas editables](../../diagramas/CU-13.drawio)
