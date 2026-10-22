# CU-05 — Gestionar médicos y especialidades

**Módulo:** Médicos y especialidades. **Ciclo:** 1. **Prioridad:** Alta. **Requisitos:** RF03 y RF04.

El usuario confirmó el 22/10/2026 conservar la numeración original del proyecto: CU-03 es «Registrarse como paciente», CU-04 es «Registrar y gestionar pacientes en recepción» y CU-05 es «Gestionar médicos y especialidades». Esta decisión reemplaza la numeración alternativa usada antes en esta rama. Se mantiene Laravel existente.

**Estado:** implementados médicos (RF03) y especialidades (RF04), con validación automática. La revisión visual del nuevo submódulo de médicos está pendiente. Los diagramas y capturas históricos corresponden solo a especialidades. El enunciado original completo sigue pendiente de contraste; las reglas operativas siguientes son decisiones de implementación y no se atribuyen a la docente.

## Flujo documentado de especialidades

| Campo | Especificación |
| --- | --- |
| Caso de Uso | CU-05 Gestionar médicos y especialidades — submódulo de especialidades |
| Propósito | Mantener un catálogo de especialidades sin nombres duplicados y conservar sus relaciones al desactivar entradas |
| Descripción | Listar, buscar, registrar, editar, desactivar y reactivar especialidades |
| Actores | Administrador con permiso efectivo `medicos.gestionar` |
| Actor Iniciador | Administrador |
| Pre Condición | Cuenta activa, sesión vigente, permiso efectivo y PostgreSQL disponible |
| Flujo Principal | 1.- Abrir Especialidades desde el inicio. 2.- Consultar el listado y buscar coincidencias. 3.- Seleccionar Registrar especialidad. 4.- Completar nombre y descripción opcional. 5.- Enviar el formulario. 6.- El servidor valida sesión, permiso, CSRF, tipos, longitud y unicidad normalizada. 7.- Guarda la especialidad activa en una transacción. 8.- Muestra el listado y la confirmación. |
| Flujo Secundario | 2a.- Buscar por nombre o descripción y filtrar por estado; recorrer páginas conservando filtros. 3a.- Editar una especialidad y guardar nombre/descripción con su versión. 3b.- Seleccionar Activar/Desactivar, revisar la pantalla de confirmación y confirmar o cancelar. 6a.- Entrada inválida o duplicada: mostrar errores y conservar entrada. 6b.- Sin sesión: ir al login; sin permiso: 403. 6c.- CSRF inválido: rechazar. 7a.- Versión desactualizada: rechazar y solicitar recargar, preservando la edición ya guardada. 7b.- Duplicado concurrente: revertir y mostrar error de nombre. |
| Post Condición | Registro persistido o cambio confirmado; sin borrado físico ni alteración de relaciones. Ante rechazo, no hay cambios parciales. |

## Reglas y datos

- `nombre`: obligatorio, texto de hasta 255 caracteres. Se recortan y compactan espacios. La clave de comparación se calcula en servidor, en minúsculas y sin tildes: «Pediatría» y « pediatria » son el mismo nombre.
- `descripcion`: texto opcional de hasta 255 caracteres, según la columna existente. Un valor vacío se almacena como NULL.
- `nombre_normalizado`: generado en servidor; nunca se confía en un valor enviado por el formulario. Su UNIQUE existente protege de carreras. La unicidad incluye especialidades inactivas.
- `activo`: verdadero al crear. La edición de datos no modifica el estado; el cambio de estado tiene confirmación propia.
- `id`: identidad estable. No hay acción DELETE. Desactivar no elimina vínculos con médicos ni citas.
- Autorización por permiso efectivo, además del menú. El permiso actual incluye médicos y especialidades; por defecto lo tiene ADMINISTRADOR. Una asignación explícita posterior permite delegar la operación.
- Firma de versión y bloqueo de fila para rechazar formularios desactualizados. No requiere agregar columnas.
- Formularios con CSRF, validación HTML5 y validación independiente en servidor; salidas escapadas en Blade. La confirmación es una página usable también sin JavaScript.

## Límites

El submódulo de especialidades mantiene el catálogo. La gestión de médicos se describe debajo. Horarios, disponibilidad, reservas y consulta de agenda corresponden a CU-06, CU-07 y CU-11 y todavía no están implementados. Este caso no crea usuarios ni modifica roles o contraseñas.

## Criterios de aceptación del submódulo de especialidades

1. Una cuenta con permiso efectivo puede listar, registrar, editar y cambiar estado; cuentas sin permiso y visitantes no pueden hacerlo.
2. Nombres equivalentes por mayúsculas, tildes o espacios se rechazan, aunque el registro anterior esté inactivo.
3. Los campos inválidos no llegan a persistir; modificar `id`, `nombre_normalizado` o `activo` en una petición de alta/edición no evita las reglas.
4. La búsqueda y el filtro conservan su valor al paginar; HTML aportado por usuarios se muestra como texto.
5. La desactivación/reactivación conserva ID y vínculos; la confirmación GET no modifica datos.
6. Una edición antigua no sobrescribe cambios recientes. PostgreSQL impide duplicados incluso si se omite la validación de formulario.

Consultar [trazabilidad](dependencias.md), [pruebas](pruebas.md) y [diagramas del submódulo de especialidades](../../diagramas/CU-05.drawio).

## Gestión de médicos implementada

Entrada `/medicos`, sesión vigente y permiso efectivo `medicos.gestionar`. El administrador puede buscar por código, registro profesional o nombre completo, filtrar por estado/especialidad y recorrer páginas de 15 registros. El listado conserva todos los datos y acciones en tarjetas móviles.

1. Abrir Médicos desde el inicio o el menú lateral y buscar antes de registrar.
2. Seleccionar Registrar médico, completar nombres, apellidos, registro profesional, teléfono y correo de contacto.
3. Asignar una o varias especialidades activas. Opcionalmente elegir una cuenta existente activa con permiso efectivo `agenda.propia`, sin vinculación con otro médico.
4. Validar en servidor y guardar médico, código `MED-` + ID y asignaciones en una transacción. La ficha nace activa. No se crean usuarios, horarios, citas ni historias clínicas.
5. Editar con una firma de versión que incluye campos de la ficha y estados de sus asignaciones. Una edición o confirmación desactualizada se rechaza.
6. Activar/desactivar en una pantalla de confirmación separada. GET no cambia datos. No existe DELETE.

### Reglas de médicos

- Nombres/apellidos: texto obligatorio de hasta 150 caracteres; se compactan espacios. Registro profesional: obligatorio, hasta 100 caracteres, normalizado a mayúsculas y sin espacios exteriores. PostgreSQL impide duplicados normalizados, incluso entre inactivos y ante escrituras concurrentes. La migración rechaza datos previos duplicados antes de crear el índice; no los modifica ni elimina.
- Teléfono obligatorio de hasta 50 caracteres; correo de contacto obligatorio de hasta 255, en minúsculas. No se exige correo de contacto único y no se vinculan cuentas por coincidencias de correo.
- Al guardar deben seleccionarse de 1 a 100 especialidades activas distintas. Se revalida su estado bajo bloqueo. Las asignaciones retiradas del catálogo activo cambian a `activo=false` sin borrar filas; las relaciones con especialidades desactivadas se conservan y no son editables hasta que vuelvan a estar activas.
- La selección de cuenta es explícita y opcional; una cuenta solo puede asociarse con un médico. Una cuenta actual desactivada o que perdió permisos puede conservarse al editar, pero no asignarse a una nueva ficha. Seleccionar «Sin cuenta vinculada» retira la asociación. Las reglas se revalidan dentro de la transacción y comparten el bloqueo de administración de permisos.
- Desactivar un médico no cambia su cuenta, asignaciones, horarios ni citas. Se informa la cantidad de citas futuras pendientes para coordinar su atención. Reactivar exige una especialidad activa asignada.
- La sesión y el permiso se verifican en las rutas y en el servicio; POST/PUT/PATCH requieren CSRF. Salidas Blade escapadas. Los eventos guardan solo IDs y estado después del commit.

### Criterios de aceptación de médicos

Registro y edición completos; código generado; ninguna cuenta creada implícitamente; especialidades asignadas; cuenta opcional elegible y única; rechazo de registro duplicado también en PostgreSQL; rollback ante colisiones; conservación de identidad y relaciones históricas; rechazo de versiones antiguas; activación/desactivación con confirmación; permisos, CSRF y escape HTML; búsqueda, paginación y adaptación del listado verificadas automáticamente. No se acredita una prueba de carga simultánea ni una revisión visual de médicos en navegador.
