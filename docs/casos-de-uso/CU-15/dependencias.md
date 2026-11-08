# CU-15 — Trazabilidad

| Elemento | Implementación |
| --- | --- |
| Entrada | GET `/consultas`; GET y POST `/consultas/{cita}` |
| Lógica | [ConsultaController](../../../sistema/backend/app/Http/Controllers/AtencionClinica/ConsultaController.php) |
| Interfaz | [Vista principal](../../../sistema/frontend/views/consultas/show.blade.php) |
| Datos del dominio | CitaMedica, Historia clínica, Consulta médica, Tratamiento |
| Prueba del flujo | [LucasClinicalWorkflowTest](../../../sistema/backend/tests/Feature/LucasClinicalWorkflowTest.php) |


Los permisos iniciales se asignan con `php artisan db:seed --class=RolesPermisosSeeder --no-interaction`. Los CU-13 a CU-15 usan las tablas existentes.

[Diagramas editables](../../diagramas/CU-15.drawio)
