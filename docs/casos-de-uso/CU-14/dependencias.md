# CU-14 — Trazabilidad

| Elemento | Implementación |
| --- | --- |
| Entrada | GET y PATCH `/mi-perfil` |
| Lógica | [PerfilController](../../../sistema/backend/app/Http/Controllers/Pacientes/PerfilController.php) |
| Interfaz | [Vista principal](../../../sistema/frontend/views/perfil/edit.blade.php) |
| Datos del dominio | Paciente, Perfil de salud declarado, Contacto de emergencia |
| Prueba del flujo | [LucasClinicalWorkflowTest](../../../sistema/backend/tests/Feature/LucasClinicalWorkflowTest.php) |


Los permisos iniciales se asignan con `php artisan db:seed --class=RolesPermisosSeeder --no-interaction`. Los CU-13 a CU-15 usan las tablas existentes.

[Diagramas editables](../../diagramas/CU-14.drawio)
