# CU-12 — Pruebas

Archivo `sistema/backend/tests/Feature/Citas/HistorialTest.php`: 14 pruebas con transacciones revertidas en PostgreSQL aislado.

| Grupo | Resultado esperado |
| --- | --- |
| Acceso | Sesión y permiso requeridos; médico sin permiso de historial recibe 403 |
| Selección | Personal debe seleccionar ficha antes de listar; paciente obtiene solo la propia |
| Conservación | Incluye citas pasadas, futuras, canceladas y fichas inactivas |
| Propiedad | IDs ajenos por query o ruta reciben 403; sin ficha no hay resultados globales |
| Búsqueda | Código, CI, nombre completo, comodines literales y máximo de opciones |
| Fechas | Rango inclusivo, límites independientes y rechazo de rango invertido |
| Estados | Atendida incluye variantes históricas de almacenamiento |
| Paginación | Quince filas, total y filtros conservados; página vacía segura |
| Revocaciones | Cambios de permiso y de vínculo toman efecto en la petición siguiente |
| Privacidad | HTML escapado; sin motivos clínicos/cancelaciones; no cambia citas ni auditoría |

La prueba integrada `CitaIntegrationTest` verifica además que una misma cita reprogramada, confirmada y cancelada aparece conservada en el historial. El resultado global y las capturas se registran en `docs/pruebas/Lucas/RESULTADOS.md` al completar la integración. Los tests de HTTP no acreditan por sí solos geometría visual.
