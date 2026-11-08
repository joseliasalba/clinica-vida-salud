# CU-13 — Generar reportes administrativos

**Ciclo:** 2. **Responsable de la entrega:** Lucas. **Fuente:** Sistema Web de Gestión de Citas Médicas, catálogo y planificación del ciclo 2 (páginas 43–47 del PDF).

| Campo | Especificación |
| --- | --- |
| Objetivo | Consultar citas y contar pacientes distintos en el conjunto filtrado. |
| Actor principal | Administrador o personal con permiso |
| Actores secundarios | No intervienen en el flujo principal. |
| Precondiciones | Sesión vigente y permiso efectivo `reportes.consultar`, asignado por defecto al administrador. |
| Evento inicial | Abrir «Reportes administrativos». |
| Resultado de éxito | Se muestran las citas paginadas y los totales del conjunto filtrado. No se modifican datos. |
| Garantía ante fallo | La petición rechazada no modifica los datos de la operación. |

## Flujo principal

| Paso | Acción del actor | Respuesta del sistema |
| --- | --- | --- |
| 1 | Abre Reportes administrativos | Verifica sesión y permiso; muestra filtros y resultados iniciales. |
| 2 | Selecciona filtros y genera el reporte | Valida fechas, médico, especialidad y estado. |
| 3 | Revisa el reporte | Filtra citas por fecha de inicio, cuenta citas y pacientes distintos, y muestra filas paginadas. |
| 4 | Cambia de página o limpia filtros | Conserva filtros al paginar y vuelve a la consulta general al limpiar. |

## Flujos alternativos y excepciones

| Código | Paso | Situación | Respuesta |
| --- | --- | --- | --- |
| A1 | 2 | Fechas inválidas o rango invertido | Muestra errores de validación. |
| A2 | 3 | No hay coincidencias | Muestra totales en cero y un estado vacío. |
| E1 | 1 | No hay sesión o permiso | Redirige al acceso o deniega la solicitud. |

## Reglas de negocio y permisos

- Los extremos del rango de fechas son inclusivos. El filtro se aplica al inicio de la cita.
- El total de pacientes cuenta pacientes distintos en las citas del reporte; no equivale al total de fichas registradas.
- El listado usa el código del paciente y no muestra información clínica. No hay exportación.

## Datos utilizados

Filtros de fecha, médico, especialidad y estado. Citas médicas, ficha identificada por código, médico y especialidad.

## Criterio de aceptación

- Dado un filtro de estado, el reporte muestra solo las citas coincidentes y cuenta una vez a un paciente con varias citas.

## Evidencia y pendientes

Ver [trazabilidad](dependencias.md), [pruebas](pruebas.md) y [diagramas editables](../../diagramas/CU-13.drawio).

Comprobar las combinaciones de filtros y el diseño en navegador. Confirmar si la entrega requiere un reporte de fichas de pacientes independiente del reporte de citas.
