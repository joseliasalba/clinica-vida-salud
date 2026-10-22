# CU-03 — Gestionar especialidades médicas

**Módulo:** Especialidades. **Ciclo:** 1. **Prioridad:** Alta. **Requisito:** RF04.

La numeración procede de la imagen entregada por el usuario el 22/10/2026. Sustituye, para este trabajo, el antiguo CU-03 de autorregistro. El usuario confirmó continuar con Laravel existente. El enunciado original completo sigue pendiente de contraste; no se atribuyen a la docente decisiones adicionales.

| Campo | Especificación |
| --- | --- |
| Caso de Uso | CU-03 Gestionar especialidades médicas |
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

Este CU mantiene el catálogo. La selección de especialidades activas al reservar se verificará en los CU de disponibilidad y programación; todavía no se presenta como implementada. No administra médicos, horarios, citas ni roles. No se modifica el esquema ni se introduce una dependencia nueva.

## Criterios de aceptación

1. Una cuenta con permiso efectivo puede listar, registrar, editar y cambiar estado; cuentas sin permiso y visitantes no pueden hacerlo.
2. Nombres equivalentes por mayúsculas, tildes o espacios se rechazan, aunque el registro anterior esté inactivo.
3. Los campos inválidos no llegan a persistir; modificar `id`, `nombre_normalizado` o `activo` en una petición de alta/edición no evita las reglas.
4. La búsqueda y el filtro conservan su valor al paginar; HTML aportado por usuarios se muestra como texto.
5. La desactivación/reactivación conserva ID y vínculos; la confirmación GET no modifica datos.
6. Una edición antigua no sobrescribe cambios recientes. PostgreSQL impide duplicados incluso si se omite la validación de formulario.

Consultar [trazabilidad](dependencias.md), [pruebas](pruebas.md) y [diagramas](../../diagramas/CU-03.drawio).
