# CU-01 y CU-02 en Enterprise Architect

## Modelo general actualizado

La [entrega general de actores, casos y paquetes](General_Proyecto/README.md) contiene un modelo EA independiente y 22 diagramas del alcance completo: lista de actores, modelo estructurado, identificación de paquetes, trazabilidad paquete-caso y vistas por paquete. Incluye CU-01 a CU-12 con su estado actual y CU-13 a CU-17 como evolución pendiente. Las imágenes se presentan sin notas explicativas; se conservan los modelos anteriores.

Abrir **Clinica_Vida_Salud_CU01_CU02.eapx** en Enterprise Architect 15 o compatible. Es un modelo UML editable: elementos, atributos, operaciones, relaciones y diagramas; no son capturas pegadas en un proyecto vacío.

El informe **Diagramas_CU01_CU02.pdf** permite revisar las especificaciones y todos los diagramas. Las 30 imágenes exportadas por EA están en `diagramas/`. `manifest.json` relaciona sus nombres con los identificadores del modelo.

**Clinica_Vida_Salud_CU01_CU02.xmi** es una exportación XMI 2.1 adicional con los diagramas de EA. El archivo `.eapx` es la entrega principal para conservar su presentación.

## Organización

En el explorador del proyecto, expandir **Clínica Vida Salud - CU-01 y CU-02** y abrir cualquiera de los diagramas dentro de estas carpetas:

| Carpeta | Contenido |
| --- | --- |
| 00 Elementos compartidos | Actores, casos, clases, entidades, componentes y paquetes reutilizados |
| 01 Captura de requisitos | Actores, CU-01, CU-02 y diagrama general con CU-01.1 |
| 02 Análisis | Identificación, trazabilidad y encapsulación de paquetes; comunicación y clases de análisis |
| 03 Diseño | Arquitectura física y lógica; secuencias; base de datos conceptual, lógica y física; clases; estados; tiempo; navegación |
| 04 Implementación | Componentes de ambos casos de uso |

## Correspondencia con la referencia

Se analizó `C:/Users/llx21/Downloads/todos los diagramas.pdf`, de 15 páginas. Se conserva el orden de etapas, los marcos UML, los casos azules, paquetes/clases crema, componentes rosados y tablas de encabezados azules. El informe amplía la extensión para desarrollar dos casos y mantener legibilidad. No es una reproducción literal de su contenido funcional.

| Páginas de referencia | Diagramas |
| --- | --- |
| 2–4: requisitos | 01–04; especificaciones y prioridades del informe |
| 5–7: análisis | 05–11; dos variantes del diagrama 10 |
| 8–9: arquitecturas y secuencias | 12–16 |
| 10–11: base de datos | 17–19; diccionario de siete tablas |
| 12: clases dinámicas | 20–21 |
| 13: estados y tiempo | 22–25 |
| 14: navegación | 26–27 |
| 15: componentes | 28–29 |

## Adaptaciones funcionales

- Fuentes funcionales: `docs/casos-de-uso/CU-01`, `CU-01.1`, `CU-02` y el código actual. El PDF aportado se usa como referencia de presentación.
- Se representa Laravel y PostgreSQL, con el despliegue local. SMTP real figura pendiente. No se copian credenciales ni registros personales.
- Usuario engloba administrador, recepción, médico y paciente. Gestor autorizado requiere `usuarios.gestionar` efectivo.
- CU-02 tiene sesión válida como precondición; no ejecuta un «include» de login por cada modificación.
- CU-01.1 se representa como extensión del acceso. Las secuencias muestran el recorrido principal; las alternativas y garantías de fallo se detallan en las especificaciones del informe.
- Rol y Permiso son entidades conceptuales; su persistencia actual usa Query Builder. No se inventan modelos Eloquent separados.
- Se preservan varios roles por usuario, vínculos inactivos, vigencias, transacciones, revocación de sesiones y continuidad del último gestor. No se modela borrado físico.
- El diccionario se obtuvo de `information_schema`: siete tablas y 37 columnas del alcance de seguridad y cola. `timestamp` en los gráficos abrevia `timestamp without time zone`; el diccionario conserva el tipo completo. Los pivotes tienen PK compuesta; recuperación se asocia por correo sin FK declarada y `jobs` es infraestructura.
- La escala 0–2 del tiempo de CU-01 es ilustrativa; la regla real es 30 minutos desde la última actividad. El tiempo de CU-02 es ordinal, no una promesa de rendimiento.

## Regeneración

Los scripts modifican únicamente este modelo generado y sus exportaciones. Cerrarlo y guardar cualquier edición manual antes de regenerar. Se conserva una copia `.bak` si se solicita reemplazar el proyecto.

1. `node docs/modelo/enterprise-architect/generar-modelo.mjs` genera `modelo.json` desde las definiciones y `esquema-seguridad.json` (metadatos, sin datos personales).
2. Con EA abierto en su pantalla principal y sin otro proyecto, ejecutar `generar-ea.ps1` en PowerShell. Requiere EA 15, su API COM y la plantilla `EABase.eapx`; ajustar la ruta instalada si corresponde. `-Recrear` reemplaza una versión generada conservando respaldo.
3. Ejecutar `perfeccionar-ea.ps1` para notación rectangular, estados de las líneas de tiempo, colores y orden de superposición; después `ajustar-rutas-ea.ps1` para separar flechas de ida y vuelta.
4. `node docs/modelo/enterprise-architect/generar-informe.mjs` crea el PDF desde las imágenes de EA. Requiere Node y PDFKit en `.tools/pdf-reader/node_modules`; instalar allí con `npm install --prefix .tools/pdf-reader pdfkit --no-save`.

La plantilla `Model` vacía que EA incluye por defecto no contiene trabajo funcional. El modelo general de dominio en StarUML se mantiene separado y no fue sustituido.

## CU-03 a CU-07

Los cuatro tipos solicitados para estos cinco casos están en [CU03_CU07](CU03_CU07/README.md): casos de uso, comunicación, navegación y secuencia. Incluye un modelo editable independiente, 20 imágenes y un PDF de consulta. El modelo CU-01/CU-02 se conserva.

## Análisis de clases de CU-05

El [modelo de análisis de CU-05](Analisis_CU05/README.md) contiene el actor, la frontera, el control y las entidades Médico, Especialidad, Asignación de especialidad y Cuenta de usuario. Incluye el proyecto editable, la exportación XMI y la imagen PNG, sin notas explicativas visibles. Los modelos anteriores se conservan.
