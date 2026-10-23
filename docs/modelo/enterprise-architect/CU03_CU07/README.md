# Diagramas de CU-03 a CU-07

Entrega del 23/10/2026: **20 diagramas editables**, cuatro por caso. El modelo anterior CU-01/CU-02 se conserva separado.

- [Modelo de Enterprise Architect](Clinica_Vida_Salud_CU03_CU07.eapx): entrega principal, con elementos y relaciones UML editables.
- [PDF de consulta: 20 láminas](../../../../output/pdf/Diagramas_CU03_CU07.pdf).
- [Exportación XMI 2.1](Clinica_Vida_Salud_CU03_CU07.xmi).

En Enterprise Architect, abrir el archivo `.eapx`, expandir **Clínica Vida Salud - CU-03 a CU-07**, seleccionar la carpeta del caso y hacer doble clic en el diagrama.

| Caso | Casos de uso | Comunicación | Navegación | Secuencia |
| --- | --- | --- | --- | --- |
| CU-03 Registrarse como paciente | [PNG](diagramas/cu03-casos.png) | [PNG](diagramas/cu03-comunicacion.png) | [PNG](diagramas/cu03-navegacion.png) | [PNG](diagramas/cu03-secuencia.png) |
| CU-04 Registrar y gestionar pacientes en recepción | [PNG](diagramas/cu04-casos.png) | [PNG](diagramas/cu04-comunicacion.png) | [PNG](diagramas/cu04-navegacion.png) | [PNG](diagramas/cu04-secuencia.png) |
| CU-05 Gestionar médicos y especialidades | [PNG](diagramas/cu05-casos.png) | [PNG](diagramas/cu05-comunicacion.png) | [PNG](diagramas/cu05-navegacion.png) | [PNG](diagramas/cu05-secuencia.png) |
| CU-06 Configurar horarios de atención | [PNG](diagramas/cu06-casos.png) | [PNG](diagramas/cu06-comunicacion.png) | [PNG](diagramas/cu06-navegacion.png) | [PNG](diagramas/cu06-secuencia.png) |
| CU-07 Consultar disponibilidad y reservar cita | [PNG](diagramas/cu07-casos.png) | [PNG](diagramas/cu07-comunicacion.png) | [PNG](diagramas/cu07-navegacion.png) | [PNG](diagramas/cu07-secuencia.png) |

## Formato y alcance

Se sigue la presentación del PDF de referencia `todos los diagramas.pdf`, especialmente las páginas 3, 6, 9 y 14: marcos UML, casos azules, navegación crema, iconos de actor/interfaz/control/entidad, mensajes numerados y fragmentos opcionales. El contenido corresponde al código Laravel actual y a nuestra numeración.

Los diagramas de comunicación contienen asociaciones y mensajes nativos de EA; las secuencias tienen líneas de vida, llamadas y retornos editables. Las imágenes son exportaciones del modelo, no capturas insertadas como sustituto de elementos UML.

Consideraciones funcionales reflejadas:

- CU-03 registra cuenta y ficha conjuntamente. La verificación se envía después de confirmar la transacción; un fallo de correo conserva la cuenta y permite reenvío. No se presenta SMTP real como verificado.
- CU-04 cambia el estado de la ficha mediante su formulario de edición; no se inventa una ruta independiente ni se elimina al paciente físicamente.
- CU-05 gestiona médicos y especialidades, con vinculación opcional de una cuenta existente elegible. No crea cuentas automáticamente.
- CU-06 configura horarios y bloqueos y protege citas pendientes. No reprograma ni cancela reservas.
- CU-07 distingue al paciente con correo verificado del personal autorizado. Revisar un horario no lo retiene. La reserva vuelve a validar disponibilidad, bloquea médico y paciente y confirma cita y auditoría en una transacción; contempla reintentos idempotentes.

Los permisos y las fuentes se detallan en las notas de cada diagrama y en `modelo.json`. CU-08 en adelante quedan fuera de esta entrega.

## Regeneración

Guardar las ediciones manuales y cerrar el proyecto en EA antes de regenerar: los scripts reconstruyen el modelo generado, no fusionan cambios manuales. No actúan sobre el archivo CU-01/CU-02.

Desde la raíz del repositorio:

1. Ejecutar `node docs/modelo/enterprise-architect/CU03_CU07/generar-modelo.mjs`.
2. Con EA 15 abierto sin proyecto, ejecutar `docs/modelo/enterprise-architect/CU03_CU07/generar-ea.ps1 -Recrear` en PowerShell. Requiere la API COM y la plantilla instalada `EABase.eapx`. Conserva un respaldo `.bak` del modelo reemplazado.
3. Ejecutar `docs/modelo/enterprise-architect/CU03_CU07/perfeccionar-ea.ps1` para ajustar notación, mensajes y rutas y exportar las imágenes y el XMI definitivos.
4. Ejecutar `node docs/modelo/enterprise-architect/CU03_CU07/generar-informe.mjs`. Requiere PDFKit en `.tools/pdf-reader/node_modules` (`npm install --prefix .tools/pdf-reader pdfkit --no-save`).

`manifest.json` relaciona diagramas e imágenes; `elementos.json` conserva identificadores de los elementos del modelo. Los identificadores pueden cambiar al reconstruirlo.

## Verificación de la entrega

Se comprobaron cinco casos, cuatro tipos por caso, 146 elementos distintos, 45 mensajes nativos de comunicación y 105 conectores de secuencia. Se renderizaron las 20 páginas del PDF y se revisaron visualmente las láminas exportadas. Estas comprobaciones documentales no sustituyen las pruebas funcionales pendientes del sistema.
