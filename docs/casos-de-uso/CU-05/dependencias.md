# CU-05 — Dependencias y trazabilidad de especialidades

## Archivos del módulo

Las rutas se expresan respecto a la raíz del repositorio.

| Capa | Archivos | Responsabilidad |
| --- | --- | --- |
| Rutas | `sistema/backend/routes/especialidades.php`, `routes/web.php` | Siete endpoints con sesión protegida y permiso `medicos.gestionar` |
| Controlador | `sistema/backend/app/Http/Controllers/Especialidades/EspecialidadController.php` | Búsqueda, paginación y coordinación de respuestas |
| Validación | `sistema/backend/app/Http/Requests/Especialidades/SaveSpecialtyRequest.php`, `ChangeSpecialtyStateRequest.php` | Datos permitidos, longitudes, versión y autorización |
| Servicio | `sistema/backend/app/Services/Especialidades/SpecialtyManagement.php` | Transacciones, bloqueo de fila, versión y errores de unicidad |
| Modelo | `sistema/backend/app/Models/Especialidad.php` | Tabla existente sin timestamps; normalización y firma de versión |
| Pruebas | `sistema/backend/tests/Feature/Especialidades/SpecialtyManagementTest.php`, `database/factories/EspecialidadFactory.php` | Casos HTTP y servicio, datos ficticios, rollback |
| Interfaz | `sistema/frontend/views/especialidades/{index,form,estado}.blade.php`, `views/layouts/especialidades.blade.php` | Listado, formulario y confirmación accesible |
| Integración visual | `sistema/frontend/views/inicio.blade.php`, `sistema/backend/config/navegacion.php`, `css/app.css`, `css/especialidades/especialidades.css`, componentes `responsive-table`, `table-cell` y `table-empty` | Acceso desde inicio y menú lateral por permiso; listado con tarjetas móviles y tabla en contenedores amplios |
| Diagramas | `docs/diagramas/CU-05.drawio` | Cinco páginas: CU, comunicación, clases de análisis, secuencia de alta y secuencia de edición/estado |

## Matriz RF → CU → tablas → pruebas

| Requisito/regla | CU | Tablas | Pruebas/evidencia |
| --- | --- | --- | --- |
| RF04: registrar y mantener especialidades | CU-05 | especialidades | Altas, edición, estado; recorrido de navegador |
| RF04: nombre único normalizado | CU-05 | especialidades (UNIQUE nombre_normalizado) | Duplicados con tildes/espacios/mayúsculas, inactivos y restricción PostgreSQL; captura 02 |
| RF01/RN12: acceso autorizado | CU-05 | usuarios, roles, permisos, usuario_rol, rol_permiso | Sesión requerida, 403 para roles sin permiso, permiso revocado |
| Conservación de referencias al desactivar, decisión del módulo | CU-05 | especialidades, medico_especialidad, citas_medicas | Test de cambio de estado conserva vínculos con médico y cita; capturas 03–04 |
| RNF: entrada validada y salida escapada | CU-05 | especialidades | Arrays, vacíos, longitudes, campos protegidos, HTML, búsqueda SQL y CSRF |
| Evitar pérdida de ediciones, decisión del módulo | CU-05 | especialidades | Versión HMAC + bloqueo de fila; rechazo de edición/confirmación desactualizada |

## Decisiones para explicar en defensa

Se reutiliza el esquema y permiso existentes para integrar el módulo con el trabajo del equipo. La validación de formulario explica el error al usuario; el UNIQUE de PostgreSQL sigue siendo la garantía frente a solicitudes concurrentes. La desactivación preserva las relaciones y puede revertirse. La firma de versión detecta formularios antiguos sin introducir una columna adicional; el bloqueo serializa actualizaciones de la misma especialidad.

Los diagramas son XML mxGraph importable en draw.io. Se validó su estructura; la revisión gráfica de los diagramas y su exportación PNG quedan pendientes. No confundir las capturas de la aplicación con una exportación de los diagramas.
