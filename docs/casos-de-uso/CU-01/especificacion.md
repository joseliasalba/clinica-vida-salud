# CU-01 — Iniciar y cerrar sesión

**Estado:** Implementación disponible y validación automática completada; revisión visual y aceptación final pendientes. Ver [pruebas](pruebas.md) y [dependencias](dependencias.md).

| Campo | Especificación |
| --- | --- |
| Objetivo | Identificar al usuario y permitirle acceder únicamente a las funciones autorizadas, además de finalizar su sesión |
| Actor principal | Usuario: administrador, recepción, médico o paciente |
| Actores secundarios | Ninguno |
| Requisitos relacionados | RF01 según el catálogo del proyecto. RN12, RN14 y pruebas numeradas del texto sugerido: pendientes de contrastar con el enunciado original |
| Precondiciones | Cuenta existente y activa; aplicación y PostgreSQL disponibles |
| Evento que inicia el caso | El usuario abre la pantalla de acceso o solicita cerrar su sesión |
| Resultado de éxito | Sesión autenticada y acceso al inicio autorizado, o sesión invalidada al salir |
| Garantías ante un fallo | No crear una sesión autenticada, no revelar la existencia de la cuenta y conservar los datos del usuario |

## Decisiones de arquitectura

- Se mantiene Laravel 13 con Blade y PostgreSQL. El usuario confirmó que PHP nativo era solo una sugerencia, no una restricción docente.
- Backend en `sistema/backend/`; vistas y recursos en `sistema/frontend/`.
- Se reutilizan `User` sobre `usuarios`, el proveedor `usuarios_activos`, Hash, sesiones, middleware, validaciones y Gates de Laravel.
- Se conservan `usuarios.id`, `correo`, `contrasena_hash` y `activo`, junto con `roles`, `usuario_rol`, `permisos` y `rol_permiso`. No se añade una columna de rol único ni se sustituye el esquema.
- Controlador para coordinar HTTP; Form Requests para validar; modelo y mecanismos de Laravel para persistencia y autenticación. Un servicio se añadirá solo si hay lógica que lo justifique. No se duplican PDO, CSRF, hash ni sesiones con helpers o DAO manuales.
- CU-01.1, recuperación de contraseña, se especifica por separado y no es una precondición del acceso normal.

## Flujo principal

| Paso | Acción del actor | Respuesta del sistema |
| --- | --- | --- |
| 1 | Abre `/login` | Muestra identificador, contraseña y botón de ingreso |
| 2 | Escribe su nombre de usuario o correo y contraseña | Permite enviar el formulario con o sin JavaScript |
| 3 | Envía el formulario | Valida en servidor campos, límites, CSRF y límite de intentos |
| 4 | Espera el resultado | Identifica la cuenta activa y verifica el hash sin modificar la contraseña ingresada |
| 5 | — | Regenera el identificador de sesión y limpia el contador de intentos correspondiente |
| 6 | — | Redirige siempre a `/inicio` y descarta la URL solicitada antes de autenticarse |
| 7 | Consulta su inicio | Muestra funciones según permisos efectivos; las rutas vuelven a comprobarlos en servidor |

## Flujos alternativos y excepciones

| Código | Paso de origen | Situación | Respuesta y resultado |
| --- | --- | --- | --- |
| A1 | 3 | Datos vacíos o exceden límites | Errores de validación; no conservar la contraseña en sesión ni repoblarla |
| A2 | 4 | Identificador desconocido, contraseña incorrecta o cuenta inactiva | Mismo mensaje público de credenciales incorrectas y respuesta comparable; sin sesión autenticada |
| A3 | 3 | Se supera el límite de intentos | Mensaje de demasiados intentos y espera temporal, aplicado también a identificadores inexistentes |
| A4 | 1 | Ya tiene sesión válida | Redirige al inicio autorizado |
| A5 | Cualquier ruta interna | No tiene sesión o expiró | Redirige al login sin permitir acceso al recurso |
| A6 | Cualquier ruta interna | Está autenticado, pero no tiene permiso | Devuelve 403; ocultar opciones en el menú no sustituye este control |
| A7 | Sesión abierta | La cuenta se desactiva | En la siguiente solicitud protegida se rechaza el acceso y se invalida la sesión |
| A8 | 7 | Cuenta activa sin roles o permisos vigentes | Inicio sin funciones protegidas y aviso para contactar administración; no asignar privilegios por defecto |
| A9 | 3–6 | Fallo de PostgreSQL o del almacenamiento de sesión | Error controlado sin detalles internos ni acceso parcial |
| A10 | Identificación | Un nombre de usuario podría confundirse con el correo de otra cuenta | Reservar `@` para correos en la gestión de cuentas; comprobar los datos existentes antes de implementar el acceso dual |
| A11 | Sesión abierta | Usuario cierra sesión | POST con CSRF; logout, invalidación de sesión, regeneración CSRF y redirección al login |

## Reglas de negocio y seguridad

Las decisiones del plan fueron acordadas con el usuario. Las referencias académicas pendientes no se consideran requisitos confirmados. La primera versión siempre vuelve al inicio; el retorno a una ruta interna solicitada queda fuera de esta versión hasta comprobar su autorización.

1. Login mediante nombre de usuario o correo. Se definen normalización y unicidad junto con CU-02/CU-03; nunca recortar ni cambiar mayúsculas de la contraseña.
2. Propuesta de límite inicial: cinco fallos por combinación de identificador normalizado e IP, con espera de quince minutos. Añadir límite global por IP para intentos distribuidos entre cuentas. Los valores se configuran y prueban, incluyendo el comportamiento tras vencer la espera.
3. No usar solo el bloqueo global de una cuenta: permitiría que un tercero bloquee a otra persona. Si la docente exige ese bloqueo, especificar ventana, reinicio, actualización atómica y mitigaciones antes de añadir campos.
4. Mensajes y estados HTTP no deben distinguir cuenta inexistente, inactiva o contraseña incorrecta. No exponer minutos de bloqueo específicos de una cuenta conocida.
5. El login verifica la contraseña existente. Las reglas de creación de claves no se aplican retroactivamente al formulario de ingreso.
6. Sesión por cookie HttpOnly y SameSite; Secure y HTTPS en despliegue. Sin recordar sesión en la primera versión. Propuesta: treinta minutos de inactividad, aplicada también en servidor.
7. Autorizar por permisos efectivos y vigencia de asignaciones. El modelo admite varios roles: usar un inicio común con la unión de permisos vigentes, sin seleccionar arbitrariamente un único rol.
8. El cierre de sesión requiere POST y CSRF. Las páginas protegidas usarán controles de caché apropiados para evitar mostrar información sensible después de salir.
9. Registrar eventos de autenticación sin contraseñas, tokens ni datos clínicos. La IP es información operativa, no identidad; no confiar en cabeceras de proxy no configuradas.
10. Crear la primera cuenta administradora mediante un procedimiento local con contraseña introducida de forma oculta; no incluir claves compartidas en SQL, Git ni seeders.

## Datos utilizados

- Entrada: identificador y contraseña; token CSRF; IP obtenida por la aplicación.
- Persistencia existente: usuarios, roles, asignaciones y permisos; almacenamiento de sesión y caché de intentos.
- No se requieren nuevas tablas para el CU-01 básico. `ultimo_acceso` es opcional y solo se añadirá por migración si se acuerda su necesidad.
- Salida: errores de validación, mensaje genérico de acceso fallido, sesión o cierre de sesión, inicio con funciones permitidas.

## Rutas y piezas implementadas

| Método y ruta | Uso | Protección |
| --- | --- | --- |
| GET `/login` | Mostrar formulario | Invitado |
| POST `/login` | Autenticar | Invitado, CSRF y limitación de intentos |
| GET `/inicio` | Inicio y opciones habilitadas | Autenticación y comprobación de cuenta activa |
| POST `/logout` | Salir | Autenticación y CSRF |

Controlador de sesión, validación del formulario, vistas Blade de login/inicio, estilos y JavaScript opcional. Las rutas de acceso y recuperación están en `sistema/backend/routes/auth.php`, cargadas desde `routes/web.php`. Las vistas nunca serán endpoints PHP directos. Ninguna redirección aceptará URLs externas proporcionadas por el usuario.

## Criterios de aceptación

- Dada una cuenta activa, al ingresar credenciales válidas mediante usuario o correo, se autentica y cambia el identificador de sesión.
- Dadas credenciales incorrectas, una cuenta inexistente o inactiva, se rechaza con respuesta pública equivalente.
- Dados cinco fallos dentro del límite configurado, se limita el siguiente intento incluso con clave correcta; tras vencer la espera vuelve a evaluarse.
- Dada una sesión válida, al salir, la sesión anterior deja de permitir acceso y GET `/logout` no ejecuta el cierre.
- Dado un visitante sin sesión, al acceder directamente a una ruta interna, se exige autenticación.
- Dado un usuario sin permiso, al acceder directamente a una función restringida, se obtiene 403.
- Dada una asignación expirada o desactivada, no se conceden sus permisos.
- Dada una cuenta desactivada durante una sesión, la siguiente petición protegida deniega acceso.
- Dado JavaScript deshabilitado, el servidor valida y el flujo sigue funcionando.
- Dado un POST sin CSRF válido, se rechaza. Esta comprobación requiere habilitar explícitamente la protección en la prueba HTTP o comprobarla en navegador.
- Dados varios roles, se muestran las funciones permitidas sin abrir acceso a datos ajenos.
- Dada una sesión expirada o una redirección manipulada, no se recupera acceso ni se redirige a un dominio externo.

## Plan de implementación

1. Precisar criterios pendientes y comprobar la ambigüedad de identificadores en los datos existentes.
2. Crear rutas, validación, controlador, límite de intentos y manejo completo de sesión.
3. Crear login e inicio con menús según permisos, sin simular módulos clínicos terminados.
4. Preparar creación segura de la cuenta administradora y cuentas de prueba locales por rol.
5. Añadir pruebas HTTP y de límites con PostgreSQL aislado, más revisión en navegador de CSRF, cookies, navegación posterior al logout y uso sin JavaScript.
6. Implementar CU-01.1 según su especificación y validar por separado el correo real.

## Evidencia de pruebas

Ver [pruebas.md](pruebas.md) para los comandos, el resultado de la suite y las verificaciones manuales pendientes. Las pruebas de autenticación están en `sistema/backend/tests/Feature/Auth/` y usan PostgreSQL aislado.

## Preguntas pendientes

- Contrastar las referencias RN12, RN14 y las pruebas #9/#10 con el enunciado original; los marcadores `[cite: 1]` del texto sugerido no son evidencia documental.
- Confirmar límites de intentos y tiempo de inactividad propuestos.
- Confirmar reglas de normalización del usuario y correo al crear cuentas.

## Referencias de diseño

- [Laravel: autenticación, sesiones y logout](https://laravel.com/docs/13.x/authentication).
- [OWASP: autenticación, mensajes genéricos y limitación de intentos](https://cheatsheetseries.owasp.org/cheatsheets/Authentication_Cheat_Sheet.html).
