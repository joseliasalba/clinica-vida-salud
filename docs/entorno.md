# Preparación del entorno

## Estado comprobado — 22/10/2026

Git 2.46.2 y Node.js 24.19.0 están disponibles. PostgreSQL 17.11 responde en `127.0.0.1:5432`. PHP 8.4.27 y Composer se prepararon en `.tools/`.

Laravel 13 funciona en `sistema/backend/`, conectado como `clinica_app` a la base `clinica_vida_salud`. Se aplicaron las 29 migraciones y se cargaron cuatro roles y ocho permisos iniciales. Pasaron 33 pruebas con 223 comprobaciones sobre PostgreSQL. La aplicación y sus conexiones PostgreSQL usan `America/La_Paz`. La distribución de archivos está descrita en [estructura.md](estructura.md).

## Versiones elegidas

PHP 8.4 x64 NTS, Composer 2, Laravel 13, PostgreSQL 17 y Node.js 24. Laravel 13 admite PHP 8.3–8.5. El script descarga el parche estable de PHP 8.4 y valida su SHA256 oficial. También valida el SHA384 del instalador Composer.

Los parches exactos de la aplicación están registrados en `sistema/backend/composer.lock` y `sistema/frontend/package-lock.json`.

## Preparar herramientas y aplicación

Abrir PowerShell normal en la raíz de `clinica-vida-salud` y ejecutar:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\preparar-entorno.ps1
```

La política indicada afecta solo al proceso iniciado. El script usa `.tools/`, excluido de Git, sin cambiar el PATH permanente. Requiere Internet, Node.js y Visual C++ Runtime x64; las DLL del runtime se encontraron en esta PC.

El script prepara PHP con `pdo_pgsql` y Composer; instala las dependencias de Laravel en `sistema/backend/`; prepara `.env.example` para PostgreSQL; genera una clave local e instala y compila el frontend. Conserva un `.env` existente y no ejecuta migraciones. Las pruebas se ejecutan después de preparar PostgreSQL. Si una descarga falla, se puede repetir: reutiliza herramientas y la aplicación si ya existe.

Para instalar únicamente las herramientas, añadir `-SoloHerramientas` al comando.

## Preparar PostgreSQL

Comprobar desde una terminal de Windows:

```powershell
& 'C:\Program Files\PostgreSQL\17\bin\pg_isready.exe' -h 127.0.0.1 -p 5432 -t 3
```

Si no responde, revisar el servicio PostgreSQL en Servicios de Windows y el puerto configurado. No crear otra instalación ni modificar bases existentes para resolverlo.

Cuando esté disponible, crear un usuario de aplicación y una base nuevos. Los comandos solicitan las contraseñas en la terminal; no escribirlas en el chat ni en el repositorio. Si el usuario o la base ya existen, revisar su propósito antes de reutilizarlos.

```powershell
$pgBin = 'C:\Program Files\PostgreSQL\17\bin'
& "$pgBin\createuser.exe" -h 127.0.0.1 -p 5432 -U postgres --no-superuser --no-createdb --no-createrole --pwprompt clinica_app
& "$pgBin\createdb.exe" -h 127.0.0.1 -p 5432 -U postgres --owner=clinica_app --encoding=UTF8 clinica_vida_salud
```

Para configurar la contraseña local de `clinica_app` con entrada oculta, comprobar la conexión, ejecutar las migraciones, cargar roles y permisos y pasar las pruebas:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\conectar-postgresql.ps1
```

Como alternativa, en `sistema/backend/.env` completar las credenciales locales manualmente:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=clinica_vida_salud
DB_USERNAME=clinica_app
DB_PASSWORD="contraseña elegida para clinica_app"
```

Desde la raíz del repositorio, comprobar la conexión sin modificar el esquema:

```powershell
. .\scripts\activar-entorno.ps1
Set-Location sistema/backend
php artisan config:clear
php artisan db:show
```

Verificar que aparece la base nueva `clinica_vida_salud`. Solo entonces ejecutar:

```powershell
php artisan migrate
php artisan db:seed
php artisan migrate:status
php artisan serve --host=127.0.0.1 --port=8000
```

Abrir <http://127.0.0.1:8000>. Detener el servidor con Ctrl+C. Se conservan las tablas originales de Laravel; la autenticación utiliza `usuarios`. El seeder es repetible, conserva las desactivaciones y no crea cuentas ni contraseñas predeterminadas.

## Uso diario

En una nueva terminal, desde la raíz del repositorio:

```powershell
. .\scripts\activar-entorno.ps1
Set-Location sistema/backend
php artisan serve --host=127.0.0.1 --port=8000
```

Para editar estilos con recarga, abrir otra terminal, activar el entorno, entrar en `sistema/frontend/` y ejecutar `npm.cmd run dev`.

La configuración local usa sesiones y caché en archivos y tareas síncronas. Las pruebas requieren las credenciales PostgreSQL locales del backend. Desde `sistema/backend/`, ejecutar `php artisan test`.

PHPUnit fuerza la conexión `pgsql_testing`, cuyo único esquema es `clinica_testing` en la misma base local. El arranque de las pruebas crea ese esquema y aplica sus migraciones; los casos que escriben datos usan transacciones que se revierten. No se usa `migrate:fresh` sobre los datos de desarrollo. Se requiere permiso para crear el esquema y la extensión `btree_gist`. La extensión compartida se conserva al revertir la migración de la restricción de citas. No ejecutar pruebas paralelas sobre este mismo esquema.

Los permisos iniciales están en `config/permisos.php`; los Gates comprueban usuario, rol, permiso y asignación activos, además de vigencia. Los permisos de recursos propios requieren también comprobar la propiedad del recurso al implementar cada caso de uso. Los permisos clínicos del segundo ciclo quedan pendientes de especificación.

## Validación completada y pendiente

- Completado: herramientas, compilación, conexión PostgreSQL, 29 migraciones, autenticación sobre `usuarios`, roles y permisos, horario Bolivia y 33 pruebas con 223 comprobaciones. Incluye login, logout y recuperación, además de consumo concurrente del token de restablecimiento con dos procesos.
- Pendiente: revisión en navegador, entrega SMTP real, cuenta administradora inicial y pantallas y flujos clínicos posteriores. Las reservas simultáneas de citas requieren pruebas adicionales al implementar CU-07.
- `sistema/backend/.env`, `.tools`, `vendor` y `node_modules` están excluidos de Git.

## Correo real con Brevo desde localhost

Laravel usa su transporte SMTP existente, sin paquetes adicionales. El envío real queda pendiente hasta crear la cuenta de Brevo, verificar el remitente y completar las credenciales locales. La aplicación conserva `MAIL_MAILER=log` mientras falten esos datos; en ese modo los mensajes no llegan a ninguna bandeja externa.

1. Crear una cuenta en [Brevo](https://www.brevo.com/) y completar su activación.
2. En la configuración de remitentes, agregar el correo que aparecerá como remitente y completar su verificación. Cumplir los requisitos de dominio o activación que solicite Brevo para esa cuenta.
3. En **Configuración → SMTP y API → SMTP**, copiar el **Login SMTP** y generar una **clave SMTP**. No utilizar una clave API ni la contraseña de la cuenta. El Login SMTP puede ser una dirección técnica distinta del remitente.
4. Completar `sistema/backend/.env` localmente, sin compartir las claves en el chat, capturas ni Git:

```dotenv
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME="LOGIN_SMTP_DE_BREVO"
MAIL_PASSWORD="CLAVE_SMTP_DE_BREVO"
MAIL_FROM_ADDRESS="CORREO_REMITENTE_VERIFICADO"
MAIL_FROM_NAME="Clínica Vida Salud"
MAIL_TIMEOUT=15
```

Los valores en mayúsculas son marcadores que deben reemplazarse. `MAIL_FROM_ADDRESS` debe ser el remitente verificado; no copiar ahí una dirección técnica `@smtp-brevo.com`. Si existe `MAIL_URL`, quitar su valor para que no sobrescriba estas opciones. Mantener la validación de certificados TLS habilitada.

5. Activar el entorno y recargar la configuración desde la raíz del proyecto:

```powershell
. .\scripts\activar-entorno.ps1
Set-Location sistema/backend
php artisan config:clear
```

Si el servidor no recoge el cambio, detener y volver a ejecutar `php artisan serve --host=127.0.0.1 --port=8000`. No ejecutar `config:show mail` ni publicar la configuración: puede mostrar credenciales.

6. Iniciar sesión con una cuenta de paciente cuyo correo esté pendiente y usar **Solicitar nuevo enlace** en `/verificar-correo`. Se permite un envío por minuto. Comprobar recepción y spam, abrir el enlace en el mismo equipo y navegador con esa sesión iniciada, y verificar que permite continuar a `/citas`. En Brevo, los registros de correo transaccional ayudan a distinguir envío aceptado, entrega y rechazo. Una conexión SMTP correcta no demuestra que el destinatario haya recibido el mensaje.

El servidor puede enviar mensajes desde localhost; el enlace de confirmación sigue apuntando a `APP_URL`, que en esta instalación es `http://127.0.0.1:8000`. Para que otras personas abran el sistema desde Internet, se necesita una URL pública con HTTPS y actualizar `APP_URL`; no hace falta desplegar para esta prueba en el mismo equipo.

Referencias: [Envío mediante Brevo SMTP](https://help.brevo.com/hc/en-us/articles/7924908994450-Send-transactional-emails-using-Brevo-SMTP), [Credenciales y claves SMTP](https://developers.brevo.com/docs/smtp-integration), [Problemas de remitente, activación o entrega](https://help.brevo.com/hc/en-us/articles/115000188150-Troubleshooting-Issues-with-Brevo-SMTP).

## Referencias oficiales

- [PHP en Windows](https://www.php.net/manual/en/install.windows.php)
- [Descargas PHP](https://downloads.php.net/~windows/releases/releases.json)
- [Composer](https://getcomposer.org/download/)
- [Certificados CA](https://curl.se/docs/caextract.html)
- [Instalación Laravel](https://laravel.com/docs/13.x/installation)
- [Compatibilidad Laravel](https://laravel.com/docs/13.x/releases)
