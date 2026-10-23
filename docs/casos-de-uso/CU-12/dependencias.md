# CU-12 — Trazabilidad

| Requisito/regla | Archivos | Tablas | Verificación |
| --- | --- | --- | --- |
| RF12: historial de citas | `app/Http/Controllers/Citas/HistorialController.php`, `routes/historial.php`, `frontend/views/historial/index.blade.php` | citas_medicas, pacientes, medicos, especialidades | Historial con pasadas, futuras y canceladas |
| RN12: permiso y propiedad | Controlador y middleware ProtectSession/auth | usuarios, roles, permisos y sus asignaciones; pacientes.usuario_id | Visitante, médico, perfil ajeno, cuenta sin ficha y permiso revocado |
| Datos limitados | Proyección explícita en controlador; Blade escapado | Solo campos administrativos necesarios | Sin motivo, diagnósticos ni datos de otros pacientes |
| Filtros y paginación | Validación del controlador; tabla adaptable compartida | Índices y claves existentes | Fechas inclusivas, estados, quince filas y búsqueda literal |

Las rutas se cargan desde `routes/web.php`. El menú utiliza `config/navegacion.php`; la ficha de pacientes enlaza al historial. No hay migraciones ni escrituras nuevas. Los diagramas son XML draw.io con cuatro páginas: caso de uso, comunicación, clases de análisis y secuencia.
