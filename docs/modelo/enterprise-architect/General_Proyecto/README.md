# Modelo general en Enterprise Architect

Abrir **Clinica_Vida_Salud_Modelo_General.eapx** en Enterprise Architect 15 o compatible. Contiene actores, casos de uso, paquetes, asociaciones, trazas y dependencias UML editables. Los elementos se reutilizan entre vistas; las imágenes no sustituyen el modelo.

Fecha de corte: **24/10/2026**, revisión local `7dc59ab`. Se conserva la numeración del catálogo del equipo. Los modelos CU-01/CU-02 y CU-03/CU-07 anteriores no se reemplazan.

## Entregables

- [Modelo editable EA](Clinica_Vida_Salud_Modelo_General.eapx).
- [Exportación XMI 2.1 con diagramas EA](Clinica_Vida_Salud_Modelo_General.xmi).
- [PDF de 22 diagramas](../../../../output/pdf/Diagramas_Generales_Vida_Salud.pdf).
- Imágenes individuales en `diagramas/`.

## Diagramas y correspondencia con la guía

Se conservan los marcos UML, actores, óvalos azules, paquetes claros, asociaciones y trazas de las capturas de HOUSE OF CUT y de las páginas 2 y 4 a 7 de `todos los diagramas.pdf`. Por solicitud del usuario, **no se muestran notas explicativas**; las responsabilidades, fuentes y límites se guardan en las propiedades y en este documento. Azul identifica casos implementados; amarillo y el estereotipo `pendiente` identifican casos sin finalizar. Los paquetes futuros se distinguen como `propuesto`.

El modelo estructurado se presenta en tres láminas relacionadas para mantener su legibilidad: acceso/catálogos; citas/agenda; evolución. Relacionar paquetes y casos, y vista de paquetes, tienen una lámina por paquete. La última lámina agrega las dependencias generales.

| Lámina | Diagrama |
| --- | --- |
| 1 | [Lista de actores](diagramas/01-lista-actores.png) |
| 2 | [2.5 Estructurar casos de uso - Acceso y catálogos](diagramas/02-estructura-acceso-catalogos.png) |
| 3 | [2.5 Estructurar casos de uso - Citas y agenda](diagramas/03-estructura-citas-agenda.png) |
| 4 | [2.5 Estructurar casos de uso - Alcance futuro](diagramas/04-estructura-evolucion.png) |
| 5 | [3.1.1 Identificar Paquetes](diagramas/05-identificar-paquetes.png) |
| 6 | [3.1.2 Relacionar Paquetes y Casos de Uso - seguridad_usuarios](diagramas/06-relacion-seguridad.png) |
| 7 | [3.1.2 Relacionar Paquetes y Casos de Uso - gestion_pacientes](diagramas/07-relacion-pacientes.png) |
| 8 | [3.1.2 Relacionar Paquetes y Casos de Uso - medicos_especialidades](diagramas/08-relacion-medicos.png) |
| 9 | [3.1.2 Relacionar Paquetes y Casos de Uso - horarios_atencion](diagramas/09-relacion-horarios.png) |
| 10 | [3.1.2 Relacionar Paquetes y Casos de Uso - citas_agenda](diagramas/10-relacion-citas.png) |
| 11 | [3.1.2 Relacionar Paquetes y Casos de Uso - reportes](diagramas/11-relacion-reportes.png) |
| 12 | [3.1.2 Relacionar Paquetes y Casos de Uso - atencion_clinica](diagramas/12-relacion-atencion.png) |
| 13 | [3.1.2 Relacionar Paquetes y Casos de Uso - preferencias_recordatorios](diagramas/13-relacion-preferencias.png) |
| 14 | [3.1.3 Vista de Paquetes - seguridad_usuarios](diagramas/14-vista-seguridad.png) |
| 15 | [3.1.3 Vista de Paquetes - gestion_pacientes](diagramas/15-vista-pacientes.png) |
| 16 | [3.1.3 Vista de Paquetes - medicos_especialidades](diagramas/16-vista-medicos.png) |
| 17 | [3.1.3 Vista de Paquetes - horarios_atencion](diagramas/17-vista-horarios.png) |
| 18 | [3.1.3 Vista de Paquetes - citas_agenda](diagramas/18-vista-citas.png) |
| 19 | [3.1.3 Vista de Paquetes - reportes](diagramas/19-vista-reportes.png) |
| 20 | [3.1.3 Vista de Paquetes - atencion_clinica](diagramas/20-vista-atencion.png) |
| 21 | [3.1.3 Vista de Paquetes - preferencias_recordatorios](diagramas/21-vista-preferencias.png) |
| 22 | [Vista general de dependencias entre paquetes](diagramas/22-dependencias-paquetes.png) |

## Alcance y estados

CU-01, CU-02, CU-03, CU-04, CU-05, CU-06 y CU-07 están implementados; CU-01.1 es el complemento de recuperación. CU-08, CU-09, CU-10 y CU-12 de Lucas ya están integrados y se incluyen en las vistas generales. CU-11 sigue pendiente: el listado auxiliar del médico en CU-10 no acredita la agenda completa. CU-13 a CU-17 son la evolución prevista en el catálogo; su asignación por ciclo, permisos y detalles pendientes no se presentan como código terminado.

Los cuatro roles de negocio son Administrador, Recepcionista, Médico y Paciente. Visitante representa el contexto sin sesión y Servicio de correo es un actor externo secundario. La cuenta de paciente reserva y consulta historial solo para su ficha; CU-10 del médico se limita a sus citas. El médico con solo `agenda.propia` no consulta el historial completo de un paciente. La entrega SMTP real sigue sin acreditarse. En CU-16 la responsabilidad de publicar resultados requiere definición: el paciente consulta resultados autorizados y no se modela como publicador.

Los ocho paquetes representan responsabilidades lógicas: `seguridad_usuarios` corresponde a Auth; `gestion_pacientes`, a Pacientes; `medicos_especialidades`, a Medicos/Especialidades; `horarios_atencion`, a Horarios; y `citas_agenda`, a Citas. Reportes, atención clínica y recordatorios son paquetes propuestos. No se añadieron esos directorios al código por dibujarlos.

Las relaciones `trace` van del paquete a sus casos y expresan trazabilidad. No se añaden `include` o `extend` solo porque un caso utilice datos de otro o requiera sesión. CU-01.1 se conserva como extensión opcional del acceso, según la especificación existente. En la vista complementaria de dependencias la flecha va del consumidor al proveedor.

## Fuentes

`docs/casos-de-uso/README.md`; especificaciones y dependencias CU-01 a CU-12; `docs/supuestos.md`; `config/permisos.php`; rutas y controladores actuales. Se verificó la integración de Lucas en `routes/web.php`, sus permisos y sus especificaciones, sin modificar el sistema ni su base de datos.

## Regeneración

Guardar ediciones manuales y cerrar el proyecto en EA antes de reconstruir. Los scripts actúan únicamente sobre este modelo generado. `-Recrear` conserva un respaldo `.bak`, pero no fusiona cambios manuales.

1. Con EA 15 abierto sin proyecto, ejecutar `generar-ea.ps1` mediante Windows PowerShell 5.1. Requiere la API COM y la plantilla `EABase.eapx` ya instalada.
2. Ejecutar `perfeccionar-ea.ps1` con el modelo general abierto para aplicar la presentación, ocultar nombres de paquetes de procedencia y exportar las imágenes y XMI definitivos.
3. Ejecutar `node docs/modelo/enterprise-architect/General_Proyecto/generar-informe.mjs` desde la raíz. Utiliza PDFKit ya disponible en `.tools/pdf-reader/node_modules`.

El código fuente del modelo es `modelo.json`; `manifest.json`, `elementos.json` y `laminas.json` conservan la correspondencia con EA y el PDF.

## Corrección de actores — 24/10/2026

El diagrama **Lista de actores** del archivo EA y su PNG/XMI se actualizaron según la tabla indicada por el usuario: cuatro roles de negocio, cada uno con responsabilidad y límite de acceso explícitos. Visitante y correo se conservan como auxiliares en las otras vistas. CU-11 sigue pendiente y requiere limitarse al profesional vinculado y definir su política administrativa. El PDF anterior no se regeneró.

Los [siete diagramas de actividad de CU-01 a CU-07](../Actividades_CU01_CU07/README.md) están en un modelo editable independiente que también incluye la tabla de actores corregida.
