# CU-12 — Consultar historial de citas

**Módulo:** Citas. **Responsable:** Lucas. **Requisito:** RF12. Se mantiene la numeración original que confirmó el equipo en la rama compartida.

| Campo | Especificación |
| --- | --- |
| Caso de Uso | CU-12 Consultar historial de citas |
| Propósito | Consultar las citas de un paciente, incluidas las pasadas y canceladas, sin perder el historial |
| Descripción | Buscar una ficha, seleccionar paciente y filtrar sus citas por rango y estado |
| Actores | Personal con `pacientes.gestionar` o `citas.gestionar`; paciente con `citas.propias` únicamente sobre su ficha vinculada |
| Actor Iniciador | Usuario autorizado |
| Pre Condición | Cuenta activa, sesión vigente y permiso efectivo |
| Flujo Principal | 1.- Abrir Historial. 2.- El personal busca por código, CI o nombre y selecciona una ficha; el paciente obtiene automáticamente la propia. 3.- Indicar opcionalmente fechas y estado. 4.- Consultar. 5.- El servidor valida filtros y propiedad. 6.- Muestra código, fecha/hora, médico, especialidad, estado y fecha de registro con total y paginación. |
| Flujo Secundario | 2a.- Sin ficha vinculada: estado vacío. 2b.- Ficha inactiva: conservar acceso del personal a su historial. 3a.- Rango inválido o formato incorrecto: mostrar errores. 5a.- Intento de seleccionar paciente ajeno: 403. 6a.- Sin coincidencias: estado vacío. 6b.- Más de quince citas: paginar conservando filtros y paciente. |
| Post Condición | Historial leído sin modificar citas ni auditoría y sin exponer citas de otros pacientes |

## Decisiones y límites

Solo se proyectan datos administrativos. No se consultan motivos, cancelaciones, diagnósticos ni JSON de auditoría para mostrarlos en esta pantalla. El médico con solo `agenda.propia` no accede al historial completo de un paciente: su alcance de agenda es CU-11, asignado a Elías.

La búsqueda ofrece hasta 25 fichas y conserva la seleccionada; se indica cuándo precisar la búsqueda. Las fichas inactivas siguen disponibles. El filtro «Atendida» incluye los valores almacenados `completada` y `atendida`. Los límites de fecha son inclusivos y pueden usarse por separado. Un paciente sin vínculo nunca recibe una consulta global.

Entrada `/historial`; acceso desde una ficha `/pacientes/{paciente}/historial`. Ambas rutas comprueban el mismo alcance y solo admiten GET.

Ver [trazabilidad](dependencias.md), [pruebas](pruebas.md) y [diagramas](../../diagramas/CU-12.drawio).
