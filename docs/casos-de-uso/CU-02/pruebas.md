# CU-02 — Evidencia de pruebas

**Fecha:** 22/10/2026, horario de Bolivia. **Estado:** pruebas automáticas aprobadas; revisión visual pendiente.

## Resultado observado

Desde `sistema/backend/`, con el entorno activado:

```powershell
php artisan test --compact
```

Resultado global: **57 pruebas aprobadas, 451 comprobaciones**. Incluye 21 pruebas nuevas de CU-02 y las pruebas previas de autenticación y base clínica. Todas usan PostgreSQL aislado en `clinica_testing`.

También se ejecutaron la compilación del frontend con Vite, el listado de las 21 rutas web y Laravel Pint. No fue necesario cambiar el esquema ni cargar datos reales para implementar el caso.

## Cobertura

| Archivo | Evidencia |
| --- | --- |
| `UserAdministrationTest.php` | 12 pruebas: autorización, todas las pantallas, creación, normalización, hash y varios roles; rechazo de duplicados y selecciones inválidas; edición, historial, revocación, autoprotección, filtros, paginación, XSS, CSRF y entradas de tipo incorrecto |
| `RoleAdministrationTest.php` | 8 pruebas: alta y edición de roles, permisos efectivos, desactivación/reactivación, nombres base, permisos inválidos, conservación de vínculos y protección de la administración |
| `UserAdministrationConcurrencyTest.php` | 1 prueba con dos procesos PHP y dos conexiones PostgreSQL: dos gestores intentan desactivarse mutuamente; solo una operación se confirma y queda un gestor efectivo |

Los archivos están bajo `sistema/backend/tests/Feature/Auth/`. El auxiliar concurrente solo admite el entorno de pruebas y se eliminan sus cuentas efímeras al terminar.

La suite previa de CU-01 tenía una prueba intermitente por usar `CURRENT_TIMESTAMP` al insertar una asignación con precisión de segundos: el redondeo podía situarla brevemente en el futuro. Los datos de esas pruebas ahora indican explícitamente una fecha anterior; se mantiene intacta la validación de vigencia de la aplicación.

## Recorrido manual propuesto

1. Ingresar como cuenta con permiso de administración y abrir `http://127.0.0.1:8000/usuarios`.
2. Crear una cuenta de prueba con un rol; comprobar validación, búsqueda y filtro de estado.
3. Editar sus roles y comprobar las funciones visibles al entrar con esa cuenta en otro navegador.
4. Desactivarla, comprobar que pierde acceso y reactivarla; la sesión vieja no debe revivir.
5. Crear un rol adicional con permisos específicos, asignarlo y desactivar/reactivar uno de esos permisos.
6. Intentar desactivar la propia cuenta o quitarse la administración: debe rechazarse.
7. Revisar móvil, navegación por teclado, errores y formularios con JavaScript deshabilitado.

Este recorrido no se marca como ejecutado: no hay un navegador conectado disponible para inspección visual. Las pruebas HTTP sí ejercitan los formularios y el servidor. No se enviaron correos ni se modificaron permisos de cuentas reales durante la implementación.

Volver a [especificación](especificacion.md) o [dependencias](dependencias.md).
