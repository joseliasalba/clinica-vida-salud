# Actividades CU-01 a CU-07 y actores corregidos

Abrir [el modelo editable de Enterprise Architect](Clinica_Vida_Salud_Actividades_CU01_CU07.eapx). Cada carpeta CU contiene su diagrama de actividad. La carpeta **ACTORES** contiene el diagrama con las responsabilidades y límites de acceso indicados por el usuario. No se genera PDF.

| Caso | Diagrama |
| --- | --- |
| CU-01 Iniciar y cerrar sesión | [Actividad](diagramas/cu01-actividad.png) |
| CU-02 Administrar usuarios, roles y permisos | [Actividad](diagramas/cu02-actividad.png) |
| CU-03 Registrarse como paciente | [Actividad](diagramas/cu03-actividad.png) |
| CU-04 Registrar y gestionar pacientes en recepción | [Actividad](diagramas/cu04-actividad.png) |
| CU-05 Gestionar médicos y especialidades | [Actividad](diagramas/cu05-actividad.png) |
| CU-06 Configurar horarios de atención y bloqueos | [Actividad](diagramas/cu06-actividad.png) |
| CU-07 Consultar disponibilidad y reservar cita | [Actividad](diagramas/cu07-actividad.png) |
| Administrador, Recepcionista, Médico y Paciente | [Actores y límites](diagramas/actores-corregidos.png) |

Se incluye [XMI 2.1](Clinica_Vida_Salud_Actividades_CU01_CU07.xmi) con las imágenes auxiliares de EA. Los nodos iniciales/finales, acciones, decisiones, mezclas y flujos con guardas son UML nativo editable. Las etiquetas de las acciones distinguen al actor del sistema. Los recorridos alternativos que vuelven al formulario convergen mediante mezclas explícitas, no mediante un join implícito en la acción.

## Alcance

Los flujos se contrastaron con las fuentes de código registradas en `modelo.json` al 24/10/2026. Incluyen autorización, validación, transacciones, rechazo, cancelación y reintentos según cada caso. CU-03 contempla verificación de correo sin afirmar entrega SMTP real. CU-07 distingue al paciente propietario del personal autorizado, la revisión sin retención y la reserva idempotente; no confunde el comprobante con una confirmación de atención de CU-10.

El diagrama de actores refleja la tabla acordada: administración según permisos, recepción sin configuración de médicos/horarios en el conjunto inicial, médico limitado al profesional vinculado en el futuro CU-11 y paciente limitado a su ficha y comprobantes. CU-11 y su política de consultas administrativas permanecen pendientes. Visitante y servicio de correo son participantes auxiliares de otras vistas y no se añaden como roles de negocio de esta tabla.

Verificación: siete diagramas de actividad y uno de actores, con 164 elementos editables. Se comprobó alcance desde el inicio, existencia de recorridos hasta el final, guardas de decisión y mezclas explícitas; se revisaron visualmente las ocho imágenes exportadas. `validacion.json` registra las comprobaciones de estructura. No se modificó código funcional.

La misma corrección de actores se aplica a [General_Proyecto](../General_Proyecto/README.md), conservando sus demás diagramas y los actores auxiliares usados allí. No se modifican los permisos del sistema por cambiar la documentación.

## Regeneración

Desde la raíz del repositorio, con Enterprise Architect 15 iniciado y las ediciones manuales guardadas:

1. `node docs/modelo/enterprise-architect/Actividades_CU01_CU07/generar-modelo.mjs`.
2. `powershell.exe -NoProfile -ExecutionPolicy Bypass -File docs/modelo/enterprise-architect/Actividades_CU01_CU07/generar-ea.ps1 -Recrear`.
3. Para aplicar también la tabla al modelo general: ejecutar `actualizar-actores.mjs` con Node y luego `corregir-actores-ea.ps1` con Windows PowerShell, ambos desde esta carpeta o mediante su ruta completa.

Los scripts guardan diagramas abiertos antes de cambiar de proyecto. `-Recrear` reconstruye solo esta entrega, sin fusionar ediciones manuales; conserva `.bak`. La corrección del modelo general conserva un respaldo y modifica únicamente los actores principales, sus notas y su diagrama de presentación. Requiere API COM y plantilla `EABase.eapx` instalada. `manifest.json` identifica las exportaciones y `elementos.json` los elementos editables.
