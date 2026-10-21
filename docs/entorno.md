# Preparación del entorno

## Estado comprobado — 21/10/2026

Git 2.46.2 y Node.js 24.19.0 están disponibles. El cliente PostgreSQL 17 está instalado en `C:\Program Files\PostgreSQL\17\bin`. PHP y Composer no se encontraron en PATH ni en las ubicaciones comunes revisadas.

PostgreSQL no respondió en `127.0.0.1:5432` desde esta sesión. Esto no demuestra que esté detenido fuera de ella. Las descargas fallan por resolución de nombres desde la terminal de trabajo. **La instalación de herramientas y la aplicación Laravel siguen pendientes.**

## Versiones elegidas

PHP 8.4 x64 NTS, Composer 2, Laravel 13, PostgreSQL 17 y Node.js 24. Laravel 13 admite PHP 8.3–8.5. El script descarga el parche estable de PHP 8.4 y valida su SHA256 oficial. También valida el SHA384 del instalador Composer.

Los parches exactos de la aplicación quedarán registrados en `sistema/composer.lock` y `sistema/package-lock.json` al completar la instalación.

## Preparar herramientas y aplicación

Abrir PowerShell normal en la raíz de `clinica-vida-salud` y ejecutar:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\preparar-entorno.ps1
```

La política indicada afecta solo al proceso iniciado. El script usa `.tools/`, excluido de Git, sin cambiar el PATH permanente. Requiere Internet, Node.js y Visual C++ Runtime x64; las DLL del runtime se encontraron en esta PC.

El script prepara PHP con `pdo_pgsql` y Composer; crea Laravel en `sistema/`; prepara `.env.example` para PostgreSQL; genera una clave local; instala dependencias; compila recursos y ejecuta las pruebas iniciales. Conserva un `.env` existente y no ejecuta migraciones. Si una descarga falla, se puede repetir: reutiliza herramientas y la aplicación si ya existe.

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

En `sistema/.env`, completar las credenciales locales:

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
Set-Location sistema
php artisan config:clear
php artisan db:show
```

Verificar que aparece la base nueva `clinica_vida_salud`. Solo entonces ejecutar:

```powershell
php artisan migrate
php artisan migrate:status
php artisan serve --host=127.0.0.1 --port=8000
```

Abrir <http://127.0.0.1:8000>. Detener el servidor con Ctrl+C. Las migraciones iniciales pertenecen al esqueleto Laravel; todavía no representan las 24 clases del modelo de la clínica.

## Uso diario

En una nueva terminal, desde la raíz del repositorio:

```powershell
. .\scripts\activar-entorno.ps1
Set-Location sistema
php artisan serve --host=127.0.0.1 --port=8000
```

Para editar estilos con recarga, abrir otra terminal, activar el entorno, entrar en `sistema/` y ejecutar `npm.cmd run dev`.

La configuración inicial usa sesiones y caché en archivos y tareas síncronas. Esto permite abrir la página inicial antes de crear tablas; no valida por sí solo PostgreSQL. Las pruebas iniciales tampoco sustituyen las pruebas de integración con PostgreSQL.

## Validación pendiente

- Instalar herramientas con acceso a Internet y comprobar `pdo_pgsql`.
- Generar Laravel, compilar recursos y ejecutar sus pruebas.
- Confirmar conexión y migraciones sobre la base nueva.
- Versionar el código generado y ambos lockfiles; excluir `.env`, `.tools`, `vendor` y `node_modules`.

## Referencias oficiales

- [PHP en Windows](https://www.php.net/manual/en/install.windows.php)
- [Descargas PHP](https://downloads.php.net/~windows/releases/releases.json)
- [Composer](https://getcomposer.org/download/)
- [Certificados CA](https://curl.se/docs/caextract.html)
- [Instalación Laravel](https://laravel.com/docs/13.x/installation)
- [Compatibilidad Laravel](https://laravel.com/docs/13.x/releases)
