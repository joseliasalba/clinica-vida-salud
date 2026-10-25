# Clases de análisis: CU-03, CU-04, CU-06 y CU-07

Abrir [el modelo editable de Enterprise Architect](Clinica_Vida_Salud_Analisis_CU03_CU04_CU06_CU07.eapx), expandir **Clínica Vida Salud - Clases de análisis CU-03 CU-04 CU-06 CU-07** y abrir el diagrama dentro de la carpeta de cada caso.

| Caso | Imagen |
| --- | --- |
| CU-03 Registrarse como paciente | [Clases de análisis](diagramas/cu03-clases-analisis.png) |
| CU-04 Registrar y gestionar pacientes en recepción | [Clases de análisis](diagramas/cu04-clases-analisis.png) |
| CU-06 Configurar horarios de atención y bloqueos | [Clases de análisis](diagramas/cu06-clases-analisis.png) |
| CU-07 Consultar disponibilidad y reservar cita | [Clases de análisis](diagramas/cu07-clases-analisis.png) |

Se incluye [XMI 2.1](Clinica_Vida_Salud_Analisis_CU03_CU04_CU06_CU07.xmi). Las clases, atributos, operaciones y asociaciones son nativos y editables en EA. Los modelos existentes se mantienen separados. No se genera PDF.

## Lectura y alcance

Se sigue el formato de clases de análisis de CU-01/CU-02 y de la página 6 del PDF de referencia: actor, clases rectangulares crema, atributos, operaciones y asociaciones. Los estereotipos clasifican responsabilidades:

- `boundary`: formularios y pantallas por los que interactúa el actor.
- `control`: coordinación y reglas del caso de uso, realizadas por los controladores y servicios actuales.
- `entity`: conceptos y datos del dominio utilizados por el caso.

Es un modelo de análisis, no un diagrama físico de base de datos ni un catálogo literal de métodos PHP. Las operaciones describen responsabilidades y pueden ser realizadas por servicios; los atributos resumidos no crean columnas. Cada diagrama incluye sus precondiciones, fuentes y correspondencia con el código vigente al 24/10/2026.

En CU-03, Asignación de rol representa `usuario_rol`, operado con Query Builder. En CU-07, Agenda médica agrupa médico, especialidad, horarios, bloqueos y ocupación; es un concepto de análisis y no un modelo Eloquent adicional. Registro de cambio representa `cambios_cita`. Los diagramas no afirman envío real de correo, creación automática de historia clínica ni ejecución de reprogramaciones/cancelaciones desde estos casos.

## Regeneración

Desde la raíz del repositorio:

1. `node docs/modelo/enterprise-architect/Analisis_CU03_CU04_CU06_CU07/generar-modelo.mjs`.
2. Con EA 15 iniciado: `powershell.exe -NoProfile -ExecutionPolicy Bypass -File docs/modelo/enterprise-architect/Analisis_CU03_CU04_CU06_CU07/generar-ea.ps1 -Recrear`.

El generador guarda los diagramas abiertos antes de cambiar de proyecto. Reconstruye únicamente este modelo, conserva su respaldo `.bak` y no fusiona ediciones manuales. Requiere la API COM de EA y su plantilla instalada `EABase.eapx`. `modelo.json` contiene las definiciones; `elementos.json` y `manifest.json` relacionan identificadores y exportaciones.
