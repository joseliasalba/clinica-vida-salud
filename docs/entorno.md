# Preparación del entorno

## Comprobación de esta PC — 20/10/2026

| Herramienta | Resultado |
| --- | --- |
| Git | Instalado: 2.46.2.windows.1 |
| PostgreSQL | Cliente psql instalado: 17.11 |
| Servidor PostgreSQL | Inicio de servicio y conexión pendientes de comprobar |
| PHP | No localizado en PATH ni en las rutas comunes revisadas |
| Composer | No localizado en PATH ni en la ruta común revisada |
| GitHub CLI | No localizado |
| GitHub | Cuenta conectada: joseliasalba |

No encontrar PHP o Composer en estas rutas no demuestra que estén ausentes de toda la PC.

## Próximo paso técnico

1. Instalar o localizar PHP y Composer, fijando versiones compatibles con la versión de Laravel seleccionada.
2. Habilitar el controlador PHP `pdo_pgsql` y verificar las extensiones requeridas por Laravel.
3. Comprobar la conexión local a PostgreSQL y crear una base y un usuario propios de desarrollo.
4. Crear la aplicación Laravel dentro de `sistema/`; mantener la documentación en la raíz del repositorio.
5. Configurar el `.env` local, generar la clave de aplicación y añadir un `.env.example` sin secretos.
6. Probar arranque, conexión a PostgreSQL y ejecución de migraciones antes de implementar los casos de uso.

La aplicación todavía no se ha generado. No ejecutar migraciones contra una base con datos existentes sin revisar su destino.

## Comprobaciones una vez instaladas las herramientas

```powershell
git --version
php --version
php -m
composer --version
psql --version
```

`pdo_pgsql` debe aparecer en la lista de módulos de PHP. Si psql no está en PATH, en esta PC se encontró en `C:\Program Files\PostgreSQL\17\bin\psql.exe`.

## Referencias oficiales

- [Instalación de Laravel](https://laravel.com/docs/13.x/installation)
- [Crear un repositorio en GitHub](https://docs.github.com/en/repositories/creating-and-managing-repositories/creating-a-new-repository)

La referencia a Laravel 13 sirve para consultar su instalación; aún no se ha fijado esa versión en dependencias del proyecto.
