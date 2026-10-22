# CU-04 — Pruebas y evidencia

**Estado:** Implementado y validado automáticamente el 22 de octubre de 2026. Revisión visual y aceptación manual pendientes.

## Resultados observados

- Suite completa: **71 pruebas, 617 comprobaciones, todas aprobadas**. Incluye las 14 pruebas nuevas de CU-04 y las regresiones existentes de Auth/Citas.
- Aislamiento: conexión `pgsql_testing`, esquema `clinica_testing`. Las pruebas de CU-04 usan `DatabaseTransactions` y datos de factory; no crean pacientes en el esquema de trabajo.
- Laravel Pint aplicado a los archivos PHP modificados, sin errores de formato pendientes.
- Frontend compilado con Vite 8.3.5. La advertencia sobre la dependencia opcional `fontaine` es del plugin de fuentes existente; la compilación finalizó correctamente y no se añadieron dependencias.
- Las seis rutas de pacientes aparecen correctamente en `route:list`; las vistas Blade se compilaron con `view:cache`.
- Migración del índice de CI aplicada correctamente en la base local. No se sembraron pacientes ni cuentas de demostración en ella.

Comandos desde `sistema/backend`, con el entorno PHP del proyecto activado:

```powershell
php vendor/bin/pint --dirty --format agent
php artisan test --compact
php artisan route:list --path=pacientes --except-vendor
php artisan migrate --path=database/migrations/2026_10_22_160330_add_normalized_ci_index_to_pacientes_table.php --no-interaction
php artisan view:cache
npm --prefix ../frontend run build
```

La ejecución local usó `.tools/php/php.exe` y `.tools/php/php.ini` mediante rutas relativas; los comandos anteriores expresan sus equivalentes tras activar el entorno.

## Cobertura de CU-04

`tests/Feature/Pacientes/PatientManagementTest.php` verifica accesos de invitados, médico/paciente sin permiso y recepción/administrador; todas las pantallas; enlace desde inicio; registro sin cuenta; CI normalizado; varios CI vacíos; código generado e inyección de campos; rechazo de datos inválidos; conservación de cuenta/historia al cambiar estado; formulario desactualizado; búsquedas, comodines literales, paginación y término `0`; escape HTML también en encabezados; CSRF activado explícitamente; permiso revocado; duplicados rechazados por PostgreSQL incluso al omitir el Form Request; y rollback si colisiona el código definitivo.

La protección de concurrencia combina unicidad en PostgreSQL, transacción y bloqueo de fila. Se probaron el rechazo en la base, el rollback y dos versiones de una ficha; no se ejecutó una prueba multiproceso específica de CU-04. La suite completa sí incluye las pruebas concurrentes existentes de otros módulos.

## Matriz prevista

| Área | Escenarios y resultado esperado |
| --- | --- |
| Autorización | Invitado redirigido al login; médico/paciente sin permiso reciben 403 en cada acción; recepción y administrador con permiso acceden |
| Permisos vigentes | Retirar o desactivar el permiso/rol impide la siguiente petición; no basta ocultar el enlace |
| Registro | Datos válidos crean una ficha y un código único; no crean usuarios, roles, citas o datos clínicos |
| CI | Normalización acordada; rechazo de duplicado propio de otro paciente, incluyendo inactivos y diferencias de mayúsculas; edición puede conservar su propio CI |
| Sin CI | Si se aprueba: varios pacientes con CI `NULL` y códigos distintos; vacío no se guarda como cadena compartida |
| Campos | Nombres y teléfono requeridos; fechas inexistentes o futuras, correo inválido, arrays, longitudes excesivas y estados no booleanos se rechazan |
| Integridad | Enviar `id`, `codigo`, `usuario_id` o atributos clínicos no modifica esos datos; editar conserva el vínculo previo |
| Concurrencia | Dos altas con el mismo CI normalizado no producen dos pacientes ni mensajes SQL públicos; altas distintas no comparten código |
| Búsqueda | Código, CI, nombre completo, búsqueda sin resultados, estado y paginación; `%`, `_` y comillas se tratan sin ampliar indebidamente la consulta |
| Ficha | Registro existente visible a personal autorizado; inexistente devuelve 404; no expone datos clínicos ni credenciales |
| Estado | Desactivar/reactivar conserva ID, código y referencias; no afecta al usuario vinculado; DELETE no ofrece borrado |
| Seguridad HTTP | CSRF verificado con middleware habilitado; respuestas sin caché; rutas directas protegidas |
| Salida | HTML de entrada escapado; errores legibles y sin información de base de datos |
| Navegación | Inicio → listado → registro → ficha → edición → ficha/listado, con cancelación sin guardar |
| Auditoría | Se registran acción e IDs; no se incluyen CI, contacto o datos personales en mensajes técnicos |
| Regresión | Autenticación y administración de usuarios/roles/permisos siguen funcionando |

## Verificación manual pendiente

La herramienta de navegador no ofrecía un navegador disponible en esta sesión. No se declara una revisión visual como aprobada. Las comprobaciones HTTP sí renderizan las vistas y recorren los flujos automatizados.

- Completar el flujo en navegador con un usuario de recepción y otro sin permiso.
- Comprobar foco, etiquetas, errores, teclado y funcionamiento sin JavaScript.
- Revisar listado y formulario en móvil, estados vacíos y nombres/códigos largos.
- Confirmar que el código no se edita y que desactivar una ficha no sugiere borrar ni bloquear su cuenta.
- Revisar el caso sin CI y la advertencia de buscar antes de registrar, según las decisiones finales.

## Alcance del plan de referencia

Utilizar el esquema PostgreSQL aislado `clinica_testing`; no ejecutar pruebas que borren o recreen las tablas de trabajo. Generar pacientes sintéticos mediante factories y mantener las convenciones de aislamiento existentes.

La matriz anterior conserva el plan de verificación. Los resultados observados y sus límites están en las secciones iniciales; los pasos manuales siguen pendientes y no equivalen a fallos automáticos.
