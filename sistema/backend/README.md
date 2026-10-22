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

La configuración local y las credenciales están en `.env`, excluido de Git. Consulta [la guía de entorno](../../docs/entorno.md).