# CU-05 — Revisión del submódulo de especialidades

**Fecha:** 22/10/2026. **Resultado:** aprobado por dos revisiones independientes de subagentes, solicitadas por el usuario.

| Revisión | Alcance | Comprobaciones | Dictamen |
| --- | --- | --- | --- |
| Backend y seguridad | Rutas, autorización efectiva, CSRF, validación, normalización, UNIQUE, transacciones, versión y conservación de relaciones | Sintaxis PHP, lectura de las siete rutas y código, `git diff --check` | APROBADO, sin hallazgos bloqueantes |
| Calidad e integración | Pruebas, contratos frontend/backend, accesibilidad de formularios, documentación y diagramas | Build Vite, revisión de las 14 pruebas y capturas, validación XML, IDs y referencias | APROBADO tras corregir el hallazgo P2 |

## Hallazgo resuelto

Dos páginas de `docs/diagramas/CU-05.drawio` reutilizaban el ID `1` de la capa raíz para una arista. Se renombraron las aristas numéricas con el prefijo `edge_`. La segunda revisión comprobó las cinco páginas: sin IDs duplicados, referencias inexistentes ni celdas con su propio ID como padre. El cambio conserva contenido y conexiones.

No fue necesario modificar el código funcional. Se conservan los resultados de la ejecución serial previa: 14 pruebas de CU-05 con 196 comprobaciones y suite completa de 85 pruebas con 813 comprobaciones. Los revisores no ejecutaron pruebas PostgreSQL simultáneas.

Siguen pendientes la aceptación del equipo, el contraste con el enunciado completo y la revisión gráfica/exportación PNG de los diagramas, conforme a `pruebas.md`. La aprobación corresponde a las revisiones de código e integración descritas.

## Destino de la revisión original

El usuario solicitó publicar en la rama que identifica como `chore`. La única coincidencia remota es `origin/chore/entorno-desarrollo`. Su punta es antecesora de la rama del CU; el avance directo incluye los tres commits de `main` que sirven de base y los commits de CU-05. La publicación debe conservar el historial y no usar force push.

## Ajuste de numeración

El usuario confirmó mantener la numeración original del proyecto. La documentación, el diagrama y las rutas de evidencias de especialidades se trasladan a CU-05. CU-03 queda reservado para autorregistro. Las aprobaciones y resultados anteriores corresponden a especialidades; no acreditan la implementación de médicos ni una nueva revisión funcional. Este ajuste documental se prepara en la rama chore/unificar-numeracion-cu, basada en chore/entorno-desarrollo, sin reescribir commits anteriores.
