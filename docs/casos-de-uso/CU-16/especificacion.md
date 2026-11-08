# CU-16 — Solicitar estudios y consultar/publicar resultados

**Ciclo:** 2. **Responsable de la entrega:** Lucas. **Fuente:** Sistema Web de Gestión de Citas Médicas, catálogo y planificación del ciclo 2 (páginas 43–47, y contrato con CU-20 en las páginas 76–77 del PDF).

| Campo | Especificación |
| --- | --- |
| Objetivo | Solicitar estudios, registrar y publicar sus resultados, y habilitar la consulta privada del paciente. |
| Actor principal | Médico solicitante |
| Actores secundarios | Paciente vinculado, para consultar el resultado publicado. |
| Precondiciones | Médico activo vinculado y con permiso efectivo para la acción; consulta propia elegible. Para consultar como paciente: sesión, verificación requerida, permiso `estudios.propios` y ficha vinculada. |
| Evento inicial | Abrir Estudios solicitados, continuar desde la consulta del CU-15 o entrar a Mis estudios y resultados. |
| Resultado de éxito | El estudio queda registrado o actualizado. Solo un resultado publicado puede ser consultado por el paciente correspondiente. |
| Garantía ante fallo | La petición rechazada no modifica los datos de la operación. |

## Flujo principal

| Paso | Acción del actor | Respuesta del sistema |
| --- | --- | --- |
| 1 | El médico abre Solicitar estudio y elige su consulta | Comprueba la propiedad y la consistencia entre consulta, cita e historia clínica. |
| 2 | Indica el nombre del estudio y guarda | Registra la solicitud, inicialmente solicitada. |
| 3 | Carga descripción o PDF y selecciona publicar | Valida permiso, propiedad, confirmación y versión; guarda y publica el resultado, y registra el evento. |
| 4 | El paciente abre Mis estudios y resultados | Verifica cuenta, permiso y ficha propia; muestra el resultado publicado y permite descargar el PDF, si existe. |

## Flujos alternativos y excepciones

| Código | Paso | Situación | Respuesta |
| --- | --- | --- | --- |
| A1 | 3 | El médico guarda un borrador | Guarda el contenido para el médico solicitante; el paciente no puede verlo. |
| A2 | 3 | El médico retira un resultado publicado | Exige motivo, oculta el resultado al paciente y devuelve el estudio a en proceso. |
| A3 | 2 | El médico cancela una solicitud abierta | Requiere motivo y confirmación. |
| E1 | 3 | Versión vencida, archivo inválido o falta de autorización | Rechaza la operación sin publicar. |
| E2 | 4 | Resultado ajeno, retirado o sin publicar | Impide el acceso al contenido y al archivo. |

## Reglas de negocio y permisos

- El médico solicitante carga y publica sus propios estudios. Recepción y administración no tienen acceso clínico por defecto.
- El resultado admite descripción o PDF privado de hasta 2 MB; un resultado publicado se retira antes de corregirlo.
- El paciente puede ver su solicitud; el contenido del resultado solo está disponible después de publicarse.
- El aviso por correo corresponde al CU-20 y requiere consentimiento; no forma parte del flujo principal de CU-16.

## Datos utilizados

Consulta médica, solicitud, nombre e indicaciones del estudio, resultado, versión, PDF opcional y eventos del estudio.

## Criterio de aceptación

- Dado un resultado en borrador, el paciente no puede ver ni descargar el contenido; tras publicar, solo el paciente vinculado puede hacerlo.

## Evidencia y pendientes

Ver [trazabilidad](dependencias.md), [pruebas](pruebas.md) y [diagramas editables](../../diagramas/CU-16.drawio).

Configurar almacenamiento privado persistente de PDF antes de usar archivos adjuntos en Vercel y repetir publicación y descarga allí.
