# CU-01 y CU-02: cuatro diagramas por caso

Modelo actualizado al 24/10/2026 con la misma presentación utilizada para CU-03 a CU-07. Contiene **ocho diagramas UML editables**.

Abrir [Clinica_Vida_Salud_CU01_CU02_Resumen.eapx](Clinica_Vida_Salud_CU01_CU02_Resumen.eapx) en Enterprise Architect 15 o compatible. Expandir **Clínica Vida Salud - CU-01 y CU-02**, abrir la carpeta del caso y hacer doble clic en el diagrama. El modelo anterior con treinta diagramas se conserva separado.

| Caso | Casos de uso | Comunicación | Navegación | Secuencia |
| --- | --- | --- | --- | --- |
| CU-01 Iniciar y cerrar sesión | [PNG](diagramas/cu01-casos.png) | [PNG](diagramas/cu01-comunicacion.png) | [PNG](diagramas/cu01-navegacion.png) | [PNG](diagramas/cu01-secuencia.png) |
| CU-02 Administrar usuarios, roles y permisos | [PNG](diagramas/cu02-casos.png) | [PNG](diagramas/cu02-comunicacion.png) | [PNG](diagramas/cu02-navegacion.png) | [PNG](diagramas/cu02-secuencia.png) |

También se incluye [XMI 2.1](Clinica_Vida_Salud_CU01_CU02_Resumen.xmi), con sus imágenes auxiliares. El archivo `.eapx` es la entrega principal. No se genera PDF, conforme a lo solicitado.

## Correspondencia funcional

Los diagramas se contrastaron con las rutas, controladores, middleware y servicios actuales. Las notas del modelo incluyen fuentes y precondiciones; `casos.json` contiene las definiciones funcionales.

- CU-01 muestra acceso por usuario o correo, límites de intentos, regeneración de sesión, comprobación de cuenta y vigencia, autorización y cierre con POST y CSRF. El acceso normal va a `/inicio`; la continuación interna de reserva puede llevar a `/citas`. La recuperación solo se referencia como CU-01.1, sin añadir otro caso a esta entrega.
- CU-02 muestra cuentas con múltiples roles, roles con múltiples permisos y estado del catálogo. Las mutaciones usan transacción y bloqueo global, vuelven a comprobar autorización y protegen la administración propia y al último gestor. Los vínculos retirados se conservan inactivos. Crear una cuenta no crea automáticamente un paciente o médico ni envía correo.
- Las secuencias describen recorridos válidos con fragmentos opcionales; las notas detallan rechazo, rollback y expiración. Los permisos son precondiciones, sin ejecutar un login adicional por cada cambio administrativo.

Se conserva la notación del PDF de referencia en sus páginas 3, 6, 9 y 14: casos azules, navegación crema, iconos de interfaz/control/entidad y mensajes de comunicación numerados. Los elementos, conectores y mensajes son nativos de EA.

Se verificaron ocho diagramas (dos de cada tipo), 57 elementos, 18 mensajes nativos de comunicación y 42 mensajes de secuencia. Se revisaron visualmente las ocho imágenes y se comprobó la existencia de las fuentes utilizadas.

## Regeneración

Guardar las ediciones manuales antes de regenerar. La reconstrucción no fusiona cambios manuales. Desde la raíz del repositorio:

1. `node docs/modelo/enterprise-architect/CU01_CU02_Resumen/generar-modelo.mjs`.
2. En Windows PowerShell, con EA iniciado: `powershell.exe -NoProfile -ExecutionPolicy Bypass -File docs/modelo/enterprise-architect/CU01_CU02_Resumen/generar-ea.ps1 -Recrear`.
3. `powershell.exe -NoProfile -ExecutionPolicy Bypass -File docs/modelo/enterprise-architect/CU01_CU02_Resumen/perfeccionar-ea.ps1`.

El generador guarda los diagramas abiertos y cierra el proyecto anterior antes de abrir el nuevo. `-Recrear` respalda únicamente el modelo generado en `.bak`. Requiere la API COM de EA y la plantilla instalada `EABase.eapx`; ajustar su ruta si corresponde. `manifest.json` relaciona los ocho diagramas con sus imágenes; `elementos.json` conserva sus identificadores.
