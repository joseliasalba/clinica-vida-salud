# CU-04 — Registrar y gestionar pacientes en recepción

**Estado:** Implementado; validación automática completada. Revisión visual y aceptación manual pendientes. Ver [pruebas](pruebas.md).

**Módulo:** Pacientes. **Ciclo:** 1 según el catálogo. **Fecha del plan:** 22 de octubre de 2026.

El usuario aprobó proseguir con fichas sin cuenta de acceso y CI opcional. RF02 procede del catálogo local; su redacción completa debe contrastarse con el enunciado antes de atribuirle requisitos adicionales. El plan de referencia se conserva debajo; la siguiente sección identifica las decisiones efectivamente implementadas.

## Implementación disponible

- Listado con búsqueda por código, CI y nombre completo, filtro de estado, paginación de 15 y consulta de ficha administrativa.
- Registro y edición de datos personales/contacto; desactivación y reactivación sin borrado. No se crean cuentas, historias clínicas, citas ni vínculos automáticos.
- CI opcional y normalizado a mayúsculas, con espacios extremos eliminados. Vacío se guarda como `NULL`. Los complementos y ceros iniciales se conservan; el CI es texto. Un índice único de PostgreSQL protege también de duplicados normalizados concurrentes e incluye fichas inactivas.
- Código automático `PAC-` + ID con al menos seis dígitos. La creación, la asignación del código definitivo y el guardado se realizan en una transacción. No se usa `max()+1` ni `count()+1`. Una colisión con un código reservado provoca rollback y un mensaje para reintentar.
- Obligatorios: nombres, apellidos, fecha de nacimiento y teléfono. La fecha debe ser real y no futura. Sexo se mantiene como texto opcional de hasta 30 caracteres para no inventar un catálogo; correo y dirección son opcionales. Se permite compartir correo.
- La edición conserva ID, código y `usuario_id`. Una firma de la versión de los datos y un bloqueo de fila evitan que un formulario desactualizado sobrescriba otra edición, incluso si ambas ocurren en el mismo segundo.
- Sesión protegida, permiso efectivo `pacientes.gestionar`, CSRF y respuestas sin caché. La autorización se revalida dentro de la transacción. Las vistas escapan también los encabezados que incluyen nombres.
- Los eventos `paciente.creado` y `paciente.actualizado` se registran después del commit con los IDs del actor y del paciente, sin datos personales.
- Entrada: `/pacientes`, disponible desde «Gestionar pacientes» en el inicio. Personal con permiso puede ver, registrar, editar y cambiar el estado; no hay endpoint DELETE.

El alcance de tutores, la vinculación de cuentas y la apertura de historia clínica no se implementó en CU-04. El estado de la ficha es independiente del de una cuenta vinculada. Los diagramas de Enterprise Architect existentes siguen correspondiendo a CU-01/CU-02.

| Campo | Especificación |
| --- | --- |
| Objetivo | Registrar y mantener la ficha administrativa de un paciente para que pueda utilizarse en los siguientes casos de citas y atención |
| Actor principal | Cuenta activa con permiso efectivo `pacientes.gestionar`; inicialmente recepción o administrador |
| Actores secundarios | Ninguno interactúa directamente con el sistema en este caso; el paciente proporciona sus datos al personal |
| Requisitos relacionados | RF02 según `docs/casos-de-uso/README.md`; sesión y permisos de CU-01/CU-02 |
| Precondiciones | Sesión válida, permiso efectivo, PostgreSQL disponible y tabla `pacientes` existente |
| Evento inicial | Seleccionar «Gestionar pacientes» desde el inicio |
| Éxito propuesto | Ficha registrada o actualizada, código único conservado y confirmación visible |
| Garantía de fallo propuesta | No guardar parcialmente, no crear otra ficha con el mismo CI normalizado y no modificar cuentas ni registros clínicos |

## Alcance y fuentes

**DEFINIDO por el proyecto existente:** Laravel 13, PostgreSQL, vistas Blade separadas en frontend, autenticación y autorización por permisos efectivos. La tabla `pacientes` ya incluye código único, CI nullable y único, datos personales, estado y una vinculación opcional y única con `usuarios`. Los roles iniciales administrador y recepción tienen `pacientes.gestionar`.

**PROPUESTO para esta entrega:** listado, búsqueda, filtro, paginación, registro, consulta de ficha, edición, desactivación y reactivación. No ofrecer borrado físico. Conservar la relación existente con una cuenta, si la hubiera.

**PROPUESTO fuera de esta entrega:** autorregistro CU-03; creación o vinculación de cuentas desde recepción; tutores y representación de menores; diagnósticos, historia clínica, antecedentes y salud declarada; contactos de emergencia CU-14; citas y reservas; correos automáticos y exportaciones. Estos límites deben confirmarse; no se presentan como restricciones del enunciado.

## Flujo principal: registrar paciente

| Paso | Actor | Sistema propuesto |
| --- | --- | --- |
| 1 | Abre Gestionar pacientes | Verifica sesión y permiso; muestra listado y búsqueda |
| 2 | Busca por CI, código o nombre antes de registrar | Muestra coincidencias; permite consultar fichas existentes |
| 3 | Si no encuentra la ficha, selecciona Registrar paciente | Muestra formulario de datos administrativos |
| 4 | Completa datos y guarda | Normaliza campos permitidos y valida en servidor |
| 5 | Espera confirmación | Genera código único y guarda atómicamente, sin crear una cuenta de acceso |
| 6 | Consulta el resultado | Abre la ficha guardada, muestra confirmación y permite volver al listado o editar |

## Flujos alternativos y excepciones propuestos

| Código | Situación | Respuesta |
| --- | --- | --- |
| A1 | Ya existe el CI | Rechazar el alta o cambio de CI y facilitar la búsqueda de la ficha; no fusionar ni modificar otro paciente |
| A2 | Paciente sin CI | Admitir varios CI vacíos como `NULL`, si se confirma esta decisión; identificar cada ficha mediante su código |
| A3 | Coinciden nombre y fecha de nacimiento | Permitir revisión humana; no asumir que son la misma persona ni fusionar automáticamente |
| A4 | Datos inválidos o fecha futura | Mostrar errores en español y conservar los demás datos válidos del formulario |
| A5 | Editar ficha existente | Actualizar únicamente campos permitidos; conservar ID, código y vínculo con cuenta |
| A6 | Desactivar paciente | Mostrar el cambio de estado en la edición; conservar ficha y todas sus referencias. No cambiar el estado de su cuenta de acceso |
| A7 | Reactivar paciente | Restaurar el estado de la ficha sin duplicar el registro |
| A8 | Paciente no encontrado | Responder 404, sin crear registros ni mostrar detalles internos |
| A9 | Sin sesión / sin permiso | Redirigir al login / responder 403 también ante una URL o petición directa |
| A10 | Doble envío o registro concurrente con el mismo CI | La base rechaza el duplicado; mostrar error de validación, no un error SQL al usuario |
| A11 | Error de persistencia | No confirmar una operación fallida ni producir datos parciales |
| A12 | Cancelar formulario | Volver al listado o ficha sin guardar |

## Pantallas propuestas

| Pantalla | Contenido y acciones |
| --- | --- |
| Listado | Código, nombre completo, CI o «Sin CI», teléfono, estado; búsqueda por código/CI/nombre; filtro activo/inactivo; paginación; consultar y editar |
| Registro | Datos personales y contacto, CI opcional sujeto a confirmación, estado inicial activo; guardar y cancelar |
| Ficha administrativa | Datos completos, código, estado, fechas de registro/actualización; editar y volver. No incluye información clínica |
| Edición | Datos corregibles y estado; código visible sin edición; indicación clara de que desactivar conserva registros |

Se reutilizarán el layout general, el componente de mensajes y los estilos compartidos. El módulo tendrá encabezado de pacientes, sin mostrar las pestañas de administración de usuarios a recepción.

## Datos y validaciones propuestos

| Campo existente | Regla propuesta |
| --- | --- |
| `id` | Interno; no editable ni aceptado desde formularios |
| `codigo` | Automático, único e inmutable. Formato sugerido `PAC-000001`; confirmar compatibilidad con códigos existentes antes de fijarlo |
| `ci` | Opcional si se confirma; texto, no número, para conservar ceros y complementos. Recortar extremos y normalizar mayúsculas. Unicidad normalizada, incluyendo fichas inactivas |
| `nombres`, `apellidos` | Obligatorios; recortar espacios externos, conservar acentos y escritura; máximo propuesto de 150 caracteres por campo |
| `fecha_nacimiento` | Fecha real obligatoria, no futura. No imponer edad mínima ni un máximo arbitrario |
| `sexo` | Opcional; acordar opciones y su codificación antes de imponer un catálogo nuevo |
| `telefono` | Obligatorio según la tabla actual; texto para conservar prefijo y formato. Validar longitud y caracteres de contacto sin imponer un país |
| `correo` | Opcional y con formato válido; minúsculas y espacios externos eliminados. No hacerlo único: familiares pueden compartirlo |
| `direccion` | Opcional; máximo de 255 caracteres |
| `activo` | Booleano; verdadero al registrar por defecto; desactivación/reactivación explícitas en edición |
| `usuario_id` | Nullable; conservarlo al editar. No aceptarlo por asignación masiva ni vincular cuentas automáticamente por coincidencia de correo o CI |
| `created_at`, `updated_at` | Administrados por Laravel |

Los campos de texto deben rechazar arrays y objetos. La búsqueda utilizará parámetros y tratará `%` y `_` como texto cuando provengan del usuario. Blade escapará los datos mostrados. La validación no dependerá de JavaScript.

## Reglas de seguridad e integridad propuestas

1. Proteger todas las rutas con sesión, comprobación de cuenta activa y `can:pacientes.gestionar`. No autorizar comparando solamente el nombre de un rol.
2. Crear y actualizar mediante POST/PUT con CSRF. Las respuestas administrativas conservarán la política existente de no caché.
3. Seleccionar expresamente los campos de entrada. Manipular `id`, `codigo`, `usuario_id` o datos clínicos no debe alterar esos valores.
4. Mantener las claves únicas y foráneas de PostgreSQL. No generar el código con `count()+1` ni `max()+1`; usar una secuencia o ID dentro de una transacción y comprobar el espacio de códigos existente.
5. Antes de cambiar la unicidad del CI, revisar de forma agregada si los datos existentes contienen colisiones normalizadas. Si hacen falta índices o restricciones, añadir una migración, sin reescribir migraciones ejecutadas ni fusionar pacientes silenciosamente.
6. Registrar acción, ID del actor e ID del paciente sin nombres, CI, teléfono, correo o información clínica en el mensaje de auditoría. No presentar una operación revertida como confirmada.
7. El estado de la ficha y el de la cuenta son independientes. Las restricciones de nuevas reservas para pacientes inactivos deberán especificarse y probarse al implementar CU-07.

## Plan de implementación

| Etapa | Trabajo | Resultado verificable |
| --- | --- | --- |
| 1. Cerrar decisiones | Confirmar ficha sin cuenta, política de CI vacío, opciones de sexo y alcance de tutores/historia clínica | Especificación acordada y límites explícitos |
| 2. Revisar datos y esquema | Comprobar estructura real, índices, vínculos y posibles colisiones de CI/código sin mostrar registros personales | Determinar si hace falta migración antes de tocar datos |
| 3. Backend | Modelo `Paciente`, factory de pruebas, validación, controlador, rutas y autorización; generación de código y manejo de duplicados | Registro/consulta/edición con reglas en servidor |
| 4. Frontend | Cuatro pantallas y enlace funcional desde inicio; reutilizar componentes y estilos | Flujo utilizable con y sin JavaScript |
| 5. Pruebas | Ejecutar matriz de `pruebas.md` en PostgreSQL aislado y comprobar regresiones de autenticación/permisos | Evidencia real registrada, sin modificar datos de trabajo |
| 6. Revisión | Compilar recursos, revisar navegador, teclado y móvil; comprobar datos y mensajes | CU-04 listo para aceptación funcional |

Los diagramas de Enterprise Architect se prepararán con el flujo acordado, para que representen las mismas reglas y clases que se implementen. No son requisito previo para comenzar el backend salvo decisión del usuario.

## Criterios de aceptación propuestos

- Un usuario de recepción o administrador con permiso efectivo puede completar el flujo; un paciente, médico o usuario sin ese permiso no puede consultar ni modificar fichas.
- Registrar datos válidos produce una ficha con código único y no crea una cuenta ni asigna roles.
- Registrar dos pacientes sin CI es posible si se confirma esa política; ambos tienen códigos distintos.
- Un CI duplicado, aun con diferente capitalización o en una ficha inactiva, no crea ni modifica parcialmente registros.
- Nombre, CI, código y estado permiten localizar una ficha; filtros y paginación conservan la búsqueda.
- Editar conserva el código y cualquier vínculo previo con cuenta. Campos no autorizados no se aplican.
- Desactivar/reactivar conserva la misma ficha y sus referencias; no existe endpoint de borrado.
- Fechas futuras, datos mal formados, entradas manipuladas y solicitudes sin CSRF válido se rechazan en servidor.
- Las salidas no ejecutan HTML aportado por formularios; no aparecen detalles SQL ni datos de otros módulos.

## Decisiones pendientes

1. **ACORDADO e implementado:** recepción puede registrar una ficha sin cuenta. El autorregistro y una futura vinculación requieren sus flujos explícitos.
2. **ACORDADO e implementado:** aceptar CI vacío e identificar por código único; nombre, apellidos, fecha de nacimiento y teléfono son obligatorios. No usar el CI de un familiar como si fuera el del paciente.
3. Sexo se mantiene como texto opcional. Un catálogo cerrado y la representación de tutores quedan pendientes; la tabla actual no modela tutores.
4. Contrastar el texto completo de RF02 para confirmar si exige crear una historia clínica inicial. Esa operación no se infiere de que exista la tabla `historias_clinicas`.
5. **IMPLEMENTADO:** rechazar formularios cuya versión ya no coincide con la ficha, sin perder los cambios confirmados por otra persona.

Ver [dependencias](dependencias.md) y [plan de pruebas](pruebas.md).
