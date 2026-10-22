# CU-05 — Resultados y reproducción del submódulo de especialidades

**Fecha:** 22/10/2026, Bolivia. **Resultado:** 14 pruebas del módulo aprobadas (196 comprobaciones); suite completa aprobada, 85 pruebas y 813 comprobaciones. Build de Vite y formato Pint completados.

Estos resultados corresponden al submódulo de especialidades y a la ejecución anterior al cambio de numeración. No son pruebas de gestión de médicos. Los nombres técnicos cu03-*, clinica_cu03_* y $phpCu03 se conservan en los comandos como identificadores históricos del entorno usado; no definen el código vigente del caso de uso.

## Entorno observado

PHP 8.4.25, Laravel 13.38.0 del lockfile, PostgreSQL 17.11. El PHP predeterminado en PATH es 8.3 y no puede interpretar las dependencias Symfony instaladas. PHP 8.4 requiere habilitar OpenSSL en este equipo.

La conexión de desarrollo en 127.0.0.1:5432 rechazó las credenciales locales de `clinica_app`. No se modificó su contraseña, `.env` ni sus datos. Se creó una instancia PostgreSQL temporal, limitada a 127.0.0.1:55433, dentro de `.tools/cu03-postgres/`, con usuario `clinica_test`, base `clinica_cu03_testing` y esquema `clinica_testing`. Solo contiene datos ficticios. Las pruebas usan transacciones revertidas; las migraciones preparan ese esquema aislado.

La revisión en navegador usó otra base temporal, `clinica_cu03_preview`, y el servidor local 127.0.0.1:8093. Sus credenciales de demostración no pertenecen a la instalación habitual. Las capturas no contienen pacientes reales.

## Pruebas automatizadas

| Prueba | Entrada/acción | Esperado y obtenido |
| --- | --- | --- |
| Sesión y permisos | Visitante y cuentas recepción/médico/paciente en las siete rutas | Redirección a login o 403; sin escritura. PASS |
| Pantallas y ausencia de DELETE | Administrador abre listado, alta, edición y estado; IDs inexistentes | 200; IDs inexistentes 404; DELETE 405. PASS |
| Registro | Nombre con espacios, descripción y campos protegidos manipulados | Texto limpio, clave calculada, alta activa, ID asignado por BD. PASS |
| Duplicados normalizados | Cirugía Pediátrica frente a variantes, incluyendo Unicode descompuesto y registro inactivo | Error de nombre; una sola especialidad. PASS |
| Restricción PostgreSQL | Guardar duplicado directamente por servicio | UNIQUE rechazado, rollback y error comprensible. PASS |
| Datos inválidos | Vacíos, arrays, textos >255 y expansión de normalización | Rechazo sin escritura parcial. PASS |
| Edición | Mismo nombre normalizado, cambio de descripción, intento de alterar ID/activo | Guarda datos permitidos; conserva estado/ID; rechaza nombre de otra especialidad. PASS |
| Estado e historial | Desactivar y reactivar con médico y cita vinculados | Conserva ID, nombre, relación y cita. PASS |
| Estado/versión inválidos | Booleano inválido o versión ausente | Error; registro intacto. PASS |
| Formularios antiguos | Editar o confirmar estado después de otro cambio | Error de versión y enlace para recargar; no sobrescribe. PASS |
| Filtros y paginación | Nombre sin tildes, descripción INFANTIL, estado, 18 filas | Coincidencias correctas y paginación de 15 conservando filtros. PASS |
| Búsqueda literal e inyección | `0`, `%_`, `' OR 1=1 --` | Cero no se ignora; comodines literales; inyección no altera consulta ni datos. PASS |
| HTML | Nombre/descripción `<script>alert(1)</script>` | Contenido escapado en listado, edición y confirmación. PASS |
| Permiso revocado y CSRF | Revocación de permiso; POST/PUT/PATCH sin token | 403 y 419 respectivamente; sin modificaciones. PASS |

Los tests de formularios antiguos simulan versiones concurrentes de forma determinista. La garantía de unicidad se comprueba escribiendo por servicio sin la validación HTTP; este conjunto no se presenta como una prueba de carga simultánea.

## Comandos de verificación

Desde `sistema/backend`, con PHP 8.4 y PostgreSQL de pruebas ya preparado:

```powershell
$phpCu03 = 'C:\Users\HP\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.4_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe'
$env:DB_HOST = '127.0.0.1'
$env:DB_PORT = '55433'
$env:DB_DATABASE = 'clinica_cu03_testing'
$env:DB_USERNAME = 'clinica_test'
& $phpCu03 -d extension=openssl vendor/bin/phpunit tests/Feature/Especialidades
```

Para la suite completa, los tests existentes de concurrencia lanzan procesos PHP adicionales. Se usó `PHP_INI_SCAN_DIR` apuntando a `.tools/cu03-php-ini`, con un `openssl.ini` que contiene `extension=openssl`, para que también ellos carguen OpenSSL:

```powershell
$env:PHP_INI_SCAN_DIR = (Resolve-Path ../../.tools/cu03-php-ini).Path
& $phpCu03 vendor/bin/phpunit
```

No combinar esa configuración con `-d extension=openssl` para evitar cargar la extensión dos veces. En otra máquina, usar su instalación PHP 8.4 con OpenSSL habilitado y una base de pruebas aislada autorizada. No sustituir estas variables por una base de producción.

Desde `sistema/frontend`: `npm ci --ignore-scripts --no-audit --no-fund` y `npm run build`. No se modificaron los lockfiles. El build mostró una advertencia no bloqueante de la configuración existente sobre `fontaine` opcional; no se agregó el paquete.

## Recorrido en navegador y evidencia

1. Acceder como administrador de prueba y abrir Gestionar especialidades: [listado](../../pruebas/CU-05/01-listado.png).
2. Registrar `pediatria` con Pediatría existente: muestra error y conserva el formulario: [duplicado](../../pruebas/CU-05/02-duplicado.png).
3. Registrar Dermatología y editar su descripción: se observaron mensajes de registro/actualización correctos y el valor actualizado.
4. Abrir Desactivar: [confirmación explícita](../../pruebas/CU-05/03-confirmacion.png).
5. Confirmar y buscar `dermatologia`: queda una fila Inactiva con acción Activar: [registro conservado](../../pruebas/CU-05/04-desactivada.png).

La revisión visual cubrió las pantallas en el navegador integrado a 854 px de ancho. El CSS incluye adaptación móvil; queda pendiente revisión en teléfono físico y aceptación por el equipo. Los diagramas draw.io tienen XML validado; no se exportaron PNG de diagramas ni se verificó su apariencia en el editor.

## Cómo probar en la instalación del equipo

Configurar las credenciales correctas de PostgreSQL únicamente en el `.env` local, usar PHP 8.4 con OpenSSL y `pdo_pgsql`, instalar las dependencias del lockfile y compilar recursos. Con las migraciones existentes aplicadas, iniciar el servidor y entrar con una cuenta que tenga `medicos.gestionar`. No se necesita una migración nueva para CU-05. Repetir el recorrido anterior con datos ficticios.
