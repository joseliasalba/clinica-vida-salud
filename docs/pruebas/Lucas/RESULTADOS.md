# Verificación integrada de los casos de Lucas

**Fecha:** 23/10/2026, Bolivia. **Rama:** `feat/lucas-citas-ciclo-2`, basada en la integración remota `bea7b6e` de `chore/entorno-desarrollo`.

## Resultados observados

| Comprobación | Resultado |
| --- | --- |
| Suite completa del repositorio | PASS: 183 pruebas, 2435 comprobaciones |
| Casos nuevos | CU-08: 15 pruebas; CU-09: 12; CU-10: 15; CU-12: 14; integración entre casos: 3 |
| Verificación posterior a enlaces de inicio | PASS: 20 pruebas, 723 comprobaciones de navegación, interfaz adaptable, portada e integración |
| Pint / sintaxis / diff | Formato aplicado; `git diff --check` limpio |
| Vite | Build correcto; aviso previo no bloqueante de `fontaine` opcional; ninguna dependencia agregada |
| Diagramas | Cuatro archivos `.drawio`, 16 páginas; XML, IDs únicos y referencias parent/source/target validados |
| Navegador | Registro de cambios real en datos ficticios; estados, mensajes, historial y conservación de registros comprobados |
| Adaptación | Cinco pantallas × siete anchos: 35/35 PASS sin desbordamiento horizontal |

Las cinco pantallas medidas fueron listado, reprogramación, cancelación, asistencia e historial. Se usaron marcos con anchos 320, 360, 390, 430, 768, 1024 y 1440 px en el navegador real. La anchura útil excluye la barra de desplazamiento; se comparó el ancho de contenido con el de la ventana de cada marco. La captura móvil muestra el listado convertido en tarjetas con sus datos y acciones. Las mediciones se conservan en [adaptacion.txt](adaptacion.txt).

## Evidencia funcional

1. CU-08: `DEMO-REPROGRAMAR` pasó de 09:00–09:30 a 09:30–10:00; mensaje de éxito y reserva actualizada: [captura](01-reprogramada.png).
2. CU-09: `DEMO-CANCELAR` se canceló con motivo y confirmación; el registro continúa existiendo: [captura](02-cancelada.png).
3. CU-10: `DEMO-ASISTENCIA`, ya iniciada, pasó a Atendida; no ofrece nuevas transiciones: [captura](03-atendida.png).
4. CU-12: la ficha muestra las tres citas, incluyendo cancelada y atendida; conserva fecha de creación y no muestra motivos: [captura](04-historial.png).
5. Ancho 320 px: [tarjetas móviles](05-movil-320.png).

Los nombres, teléfonos y CI usados en esta verificación son ficticios. Las capturas son de una instancia local de demostración.

## Reproducir pruebas

Se utilizó PHP 8.4.25 con OpenSSL, Laravel 13.38.0 del lockfile y PostgreSQL 17.11. La base de desarrollo habitual no se modificó. Las pruebas usan la instancia temporal 127.0.0.1:55433, base `clinica_cu03_testing`, usuario `clinica_test` y el esquema `clinica_testing`, con transacciones revertidas. La vista previa usa otra base temporal: `clinica_cu03_preview`.

Desde `sistema/backend`:

```powershell
$env:DB_HOST='127.0.0.1'
$env:DB_PORT='55433'
$env:DB_DATABASE='clinica_cu03_testing'
$env:DB_USERNAME='clinica_test'
$env:PHP_INI_SCAN_DIR=(Resolve-Path ../../.tools/cu03-php-ini).Path
$phpLucas='C:\Users\HP\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.4_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe'
& $phpLucas vendor/bin/phpunit
```

Ese directorio INI local contiene `openssl.ini` con `extension=openssl`, para que las pruebas de concurrencia existentes también lo carguen en sus procesos hijos. En otras instalaciones deben usarse sus rutas y una base de pruebas aislada preparada, nunca datos reales. No ejecutar las suites en paralelo sobre el mismo esquema.

Desde `sistema/frontend`, `npm run build`. Para la vista habitual, configurar las credenciales locales correctas y ejecutar PHP 8.4 con `public/` como raíz; no se agrega ninguna migración para estos cuatro CU.

## Límites pendientes

La integración del 24/10/2026 incorpora CU-05/06/07 de Elías. La reserva inicial conserva `/citas`; la gestión de citas existentes usa `/citas/gestion`. Se verificó el flujo reserva→reprogramación→cancelación→nueva reserva→historial con datos ficticios. La restricción física existente excluye también inasistencias; la consulta del servicio es más estricta y solo libera canceladas. Un cambio de esa restricción requiere acuerdo previo.

Los tests de conflicto comprueban rechazo real de PostgreSQL y rollback, junto a versiones antiguas; no se presentan como prueba de carga. Quedan pendientes aceptación del equipo, revisión del documento original completo, inspección visual de los diagramas/exportación PNG, teléfono físico y zoom nativo al 200 %.

## Verificación de integración del 24/10/2026

Se integraron los cambios de Elías hasta `7418053` conservando la reserva en `/citas` y trasladando las operaciones de Lucas a `/citas/gestion`. Los cuatro revisores independientes aprobaron sus suites tras integrar; los resultados y el alcance están en [REVISION.md](REVISION.md).

La revisión geométrica en navegador usó seis pantallas (reserva, gestión, reprogramación, cancelación, asistencia e historial) y siete anchos: 320, 360, 390, 430, 768, 1024 y 1440 px. Resultado: **42/42 sin desbordamiento horizontal** con datos ficticios. No sustituye la prueba pendiente en teléfono físico ni zoom nativo al 200 %.

`npm run build` y `vendor/bin/pint --dirty --format agent` pasaron. La vista previa recibió las tres nuevas migraciones de la rama compartida en su base temporal.

**Suite general final del 24/10/2026:** `vendor/bin/phpunit --stop-on-defect` aprobó **284/284 pruebas y 3592 comprobaciones**, incluyendo reservas, concurrencia y horarios del equipo y los cuatro CU de Lucas. PostgreSQL aislado, OpenSSL activo y caché Blade propia; duración 212,809 segundos. Una corrida inicial con ruta de configuración PHP errónea falló por OpenSSL ausente; se corrigió el entorno y se repitió la suite completa con resultado aprobado.
