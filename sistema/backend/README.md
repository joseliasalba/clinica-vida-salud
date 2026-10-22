# Backend de Clínica Vida Salud

Aplicación Laravel: lógica PHP, rutas, configuración, migraciones, pruebas y archivos públicos del servidor.

Las vistas Blade están en `../frontend/views/`; los estilos y JavaScript, en `../frontend/css/` y `../frontend/js/`. Vite compila desde frontend hacia `public/build/`.

Desde la raíz del repositorio, en PowerShell:

```powershell
. .\scripts\activar-entorno.ps1
Set-Location sistema/backend
php artisan serve
```

En otra terminal, desde la raíz:

```powershell
Set-Location sistema/frontend
npm.cmd run dev
```

También se pueden iniciar servidor, cola y Vite con `composer run dev` desde backend, tras activar el entorno. Para compilar: `npm.cmd run build` desde frontend. Para ejecutar las pruebas: `php artisan test` desde backend.

Las rutas de autenticación están en `routes/auth.php`, cargadas desde `routes/web.php`. Controladores y validaciones se agrupan en `app/Http/Controllers/Auth/` y `app/Http/Requests/Auth/`. Las pruebas se agrupan por función en `tests/Feature/Auth/` y `tests/Feature/Citas/`.

La configuración local y las credenciales están en `.env`, excluido de Git. Consulta [la guía de entorno](../../docs/entorno.md), [la estructura del proyecto](../../docs/estructura.md) y [el catálogo de casos de uso](../../docs/casos-de-uso/README.md).
