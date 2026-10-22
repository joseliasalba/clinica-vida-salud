# CU-02 — Administrar usuarios, roles y permisos

**Estado:** Implementado y validado automáticamente; revisión visual y aceptación manual pendientes.

**Módulo:** Auth. **Ciclo:** 1. **Fuente:** RF01 según el catálogo del proyecto y decisiones de implementación descritas aquí. Las protecciones adicionales de administración son decisiones de diseño, no reglas atribuidas sin evidencia a la docente.

| Campo | Especificación |
| --- | --- |
| Objetivo | Gestionar las cuentas y las funciones que puede utilizar cada persona |
| Actor principal | Cuenta activa con permiso efectivo `usuarios.gestionar`; inicialmente administrador |
| Actores secundarios | Ninguno |
| Precondiciones | Sesión válida de CU-01; tablas y catálogo de roles y permisos disponibles |
| Evento inicial | Seleccionar «Administrar acceso» en el inicio |
| Éxito | Cuenta o rol guardados, asignaciones actualizadas o permiso activado/desactivado |
| Garantía de fallo | No aplicar cambios parciales ni dejar al sistema sin administración; conservar las cuentas existentes |

## Flujo principal: gestionar usuarios

| Paso | Actor | Sistema |
| --- | --- | --- |
| 1 | Abre Usuarios | Lista cuentas con correo, estado y roles; permite búsqueda y filtro por estado |
| 2 | Selecciona crear o editar | Muestra datos, contraseña inicial o nueva contraseña opcional, roles y estado |
| 3 | Completa y guarda | Valida identidad única, correo, contraseña y al menos un rol activo |
| 4 | — | Comprueba nuevamente la autorización y guarda cuenta/asignaciones en una transacción |
| 5 | — | Conserva los vínculos retirados como inactivos; confirma y vuelve al listado |

## Flujos alternativos

| Caso | Respuesta |
| --- | --- |
| Crear cuenta | Usuario de hasta 50 caracteres, correo válido, contraseña confirmada de 8 a 128 caracteres y al menos un rol activo |
| Editar cuenta | La contraseña vacía conserva el hash actual; una nueva contraseña requiere confirmación |
| Desactivar cuenta | Se conserva el registro, se revocan enlaces de recuperación y se rota el token usado para invalidar sesiones previas |
| Reactivar cuenta | Recupera capacidad de login según sus roles; las sesiones anteriores a la desactivación no reviven |
| Cambiar contraseña propia | Se guarda el cambio y se cierra la sesión actual; las demás quedan rechazadas en la siguiente petición protegida |
| Crear/editar rol | Nombre único, descripción opcional, estado y al menos un permiso activo del catálogo |
| Cambiar permisos de un rol | Afecta a todas sus cuentas; los vínculos retirados se conservan inactivos |
| Desactivar rol | Sus funciones dejan de aplicarse a todas sus cuentas, sin borrar las asignaciones |
| Desactivar permiso | Lo deshabilita globalmente; conserva vínculos para una futura reactivación |
| Datos inválidos o duplicados | Errores en español, sin repoblar ni mostrar contraseñas; no guardar parcialmente |
| Sin sesión / sin permiso | Redirección al acceso / respuesta 403, incluso con una URL directa o un formulario manipulado |
| Retirar la última administración o la propia | Rechazo y rollback de la operación |

## Reglas implementadas

1. Se mantiene la relación de varios roles por usuario y varios permisos por rol.
2. Los formularios normalizan usuario y correo a minúsculas y recortan espacios externos; la contraseña no se normaliza. La unicidad se comprueba sin distinguir mayúsculas en las operaciones de CU-02.
3. El nombre de usuario permite letras ASCII, números, punto, guion y guion bajo; no admite `@` ni espacios. Así no se confunde con el correo de otra cuenta.
4. Se reutiliza la política de contraseña acordada: 8–128 caracteres, rechazo de claves comunes del catálogo local y de un carácter repetido. El hash es Argon2id.
5. No se ofrecen borrados de usuarios, roles ni permisos. La desactivación conserva referencias y datos clínicos vinculados.
6. No se pueden asignar roles inexistentes o inactivos, ni permisos inexistentes, inactivos o ajenos al catálogo del sistema.
7. Los nombres de los cuatro roles base se conservan. Se pueden crear roles adicionales. Sus permisos y estado son editables con las protecciones de continuidad administrativa.
8. Los permisos funcionales están definidos en `config/permisos.php`. La pantalla permite consultar y cambiar su estado; crear un código arbitrario no implementaría una función y queda fuera de alcance.
9. No se puede desactivar la propia cuenta ni retirar el permiso de administración del actor. Siempre debe quedar al menos una cuenta activa con `usuarios.gestionar` efectivo, considerando vigencia y estados de todos los vínculos.
10. Las operaciones de CU-02 se serializan con un bloqueo transaccional de PostgreSQL y vuelven a comprobar la autorización dentro de la transacción. Así dos gestores no pueden retirarse simultáneamente el acceso y dejar cero gestores.
11. Los cambios en roles/permisos afectan a la siguiente petición protegida, sin mantener una caché de autorizaciones por sesión.
12. Desactivar una cuenta o cambiar su contraseña rota su token de sesión. Cambiar correo o contraseña, o desactivar la cuenta, revoca sus enlaces pendientes de recuperación.
13. Los roles que siguen seleccionados conservan sus fechas si la asignación era válida. Seleccionar una asignación vencida o retirada la renueva desde ahora, sin vencimiento; la pantalla lo informa. El editor de fechas de vigencia individuales queda fuera de esta versión.
14. Los cambios confirmados registran acción e identificadores del actor y recurso, sin contraseñas ni datos personales en el mensaje de auditoría. No se registra una operación revertida como si hubiera sido confirmada.
15. La creación de una cuenta no crea automáticamente un paciente ni un médico y no envía correos. La vinculación de personas pertenece a los casos respectivos.

## Datos y rutas

Se reutilizan `usuarios`, `roles`, `permisos`, `usuario_rol`, `rol_permiso` y los tokens de recuperación. No se añadieron tablas ni migraciones.

- Usuarios: GET/POST `/usuarios`, GET `/usuarios/crear`, GET `/usuarios/{usuario}/editar`, PUT `/usuarios/{usuario}`.
- Roles: GET/POST `/roles`, GET `/roles/crear`, GET `/roles/{rol}/editar`, PUT `/roles/{rol}`.
- Permisos: GET `/permisos`, PATCH `/permisos/{permiso}`.

Todas las rutas reciben middleware web, sesión protegida, autenticación y `can:usuarios.gestionar`. Los formularios que modifican datos requieren CSRF.

## Criterios de aceptación

- Una cuenta sin permiso no puede consultar ni modificar usuarios, roles o permisos, aunque manipule la URL o el cuerpo del formulario.
- Un gestor crea cuentas con varios roles y contraseña almacenada como hash; datos adicionales enviados no otorgan acceso ni cambian identificadores.
- Correos y usuarios duplicados, contraseñas inválidas y selecciones inválidas no producen altas parciales.
- La edición sin contraseña conserva el hash y la retirada de roles conserva el vínculo inactivo.
- Usuarios desactivados no pueden ingresar y una reactivación no restaura sus sesiones antiguas.
- Desactivar roles o permisos afecta a todas sus cuentas; reactivar un permiso conserva sus vínculos previos.
- La cuenta actual no se bloquea a sí misma mediante cambios de estado o permisos.
- Dos operaciones administrativas simultáneas no dejan al sistema sin gestores efectivos.
- Listados y formularios funcionan por HTTP sin depender de JavaScript, escapan las salidas y no exponen hashes ni contraseñas repobladas.

## Evidencia y pendientes

Ver [dependencias.md](dependencias.md) y [pruebas.md](pruebas.md). Pendientes: revisión visual, teclado y navegador móvil; aceptación del equipo/docente; editor avanzado de vigencias y bitácora consultable en pantalla. Los eventos técnicos ya se registran, pero no constituyen un módulo de bitácora completo.
