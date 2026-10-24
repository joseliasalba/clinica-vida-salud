# Clínica Vida Salud

Proyecto universitario de Ingeniería de Requisitos / SI1: sistema web de gestión de citas médicas para una clínica ficticia.

## Estado actual

- Repositorio preparado para el trabajo de dos integrantes.
- Modelo de clases propuesto: 24 clases, 32 asociaciones y un único diagrama de StarUML.
- Catálogo inicial y plantilla para especificar casos de uso.
- Aplicación Laravel 13 creada en `sistema/backend/`, conectada localmente a PostgreSQL 17.
- Las migraciones incluyen el dominio clínico, la protección contra citas superpuestas y la verificación de correo. Se aplican con `php artisan migrate` en cada instalación.
- Autenticación preparada sobre `usuarios`, cuatro roles y ocho permisos iniciales; horario `America/La_Paz`.
- CU-05 — Gestionar médicos y especialidades implementado: listado, búsqueda, registro, edición, asignaciones de especialidades, vínculo opcional con cuenta existente y activación/desactivación. Entradas `/medicos` y `/especialidades`, permiso `medicos.gestionar`. Revisión visual de médicos pendiente.
- CU-06 implementado: franjas semanales por médico, duración de turnos, vigencias, bloqueos por ausencias, edición y activación/desactivación. Entrada `/horarios`, permiso `horarios.gestionar`. La modificación de agenda protege las citas pendientes.
- Validación de CU-06, 22/10/2026: **187 pruebas y 2229 aserciones aprobadas** en la batería completa, incluidas 40 pruebas de horarios/bloqueos y concurrencia real con dos procesos. Formato Pint y compilación Vite correctos; migración aplicada y acceso protegido comprobado en localhost. Revisión visual autenticada pendiente.
- Validación posterior de médicos, 22/10/2026: **147 pruebas y 1793 aserciones aprobadas**, formato Pint y compilación Vite correctos; índice de registro profesional normalizado aplicado en localhost.
- CU-03 implementado: autorregistro con cuenta y ficha vinculada, rol paciente y verificación de correo. Portada pública en `/`, registro en `/registro` y panel privado en `/inicio`.
- Menú lateral por permisos, botón de acceso persistente y listados adaptables compartidos, incluido Especialidades. Los datos institucionales de ejemplo se configuran en `sistema/backend/config/clinica.php`.
- Integración verificada el 22/10/2026: **124 pruebas aprobadas y 1535 aserciones**, con PostgreSQL en el esquema aislado `clinica_testing`; formato Pint y compilación Vite correctos. Portada, registro y CSS responden HTTP 200 en localhost. Revisión visual de la integración y entrega SMTP real pendientes.
- Acceso, cierre de sesión, recuperación y gestión de usuarios, roles y permisos implementados; revisión en navegador y entrega SMTP real pendientes. Los flujos clínicos no enumerados como implementados siguen pendientes.
- Validación de CU-07, 22/10/2026: batería completa de **219 pruebas aprobadas**; formato Pint y compilación Vite correctos. Tras reforzar los reintentos ante deadlocks de las pruebas SQL, las ocho pruebas de concurrencia de CU-06/CU-07 también pasan. Migración aplicada en localhost.
- CU-07 implementado: consulta de disponibilidad, revisión, reserva transaccional y comprobante imprimible. Entrada pública `/reservar`, módulo privado `/citas`. Incluye protección de cruces de médico/paciente, reenvíos y coordinación con CU-06. El siguiente caso de la persona 1 es CU-11, agenda médica.
- Lucas: CU-08 reprogramar, CU-09 cancelar, CU-10 confirmar/registrar atención o inasistencia y CU-12 historial implementados sobre citas existentes. Entradas `/citas` y `/historial`, con acceso por permisos en el menú. La consulta de turnos de reprogramación no sustituye la reserva CU-07 de Elías.
- Integración Lucas del 23/10/2026: **183 pruebas y 2435 comprobaciones aprobadas**, más verificación posterior de 20 pruebas de interfaz/integración tras actualizar el inicio. Cinco pantallas revisadas a siete anchos (320–1440 px): 35 mediciones sin desbordamiento horizontal. [Resultados y capturas](docs/pruebas/Lucas/RESULTADOS.md).


## Tecnologías acordadas

El usuario confirmó el 22/10/2026 continuar con Laravel existente. La numeración acordada mantiene CU-03 para autorregistro y CU-05 para médicos y especialidades. Ver [especificación, pruebas y diagramas del módulo](docs/casos-de-uso/CU-05/especificacion.md).

- PHP con Laravel.
- PostgreSQL.
- HTML y CSS; Blade para las pantallas.
- JavaScript para mejorar los formularios cuando sea necesario.

Entorno comprobado: PHP 8.4, Composer 2, Laravel 13, PostgreSQL 17 y Node.js 24. Se incluyen `composer.lock` y `package-lock.json`; las credenciales locales se mantienen fuera de Git.

## Organización

| Ruta | Contenido |
| --- | --- |
| `docs/modelo/` | Diagrama editable y notas del diseño |
| `docs/casos-de-uso/` | Catálogo y especificaciones de casos de uso |
| `docs/entorno.md` | Estado de herramientas y preparación local |
| `docs/estructura.md` | Módulos y ubicación de controladores, vistas, recursos y pruebas |
| `CONTRIBUTING.md` | Flujo de trabajo para el equipo |
| `.github/` | Plantillas de tareas y pull requests |
| `scripts/` | Preparación y activación del entorno Windows |
| `sistema/backend/` | Laravel: lógica, rutas, migraciones, pruebas y servidor |
| `sistema/frontend/` | Vistas Blade, CSS, JavaScript y compilación con Vite |

## Alcance

Cuatro perfiles: administrador, recepción, médico y paciente. El sistema contempla registro de pacientes, médicos y especialidades, horarios, reservas, reprogramaciones, cancelaciones, asistencia, agenda, historial y reportes. El paciente se registra por internet y recepción puede asistirlo.

El segundo ciclo incluye perfil de salud, consultas, tratamientos, estudios, resultados y recordatorios. Su profundidad y algunos permisos requieren aclaración.

## Reglas prioritarias

1. CI único cuando se informa; no duplicar el paciente al vincular una cuenta.
2. Un médico no puede tener citas superpuestas, incluso entre especialidades o ante reservas simultáneas.
3. Reprogramación atómica: si falla, se conserva la reserva original.
4. Una cancelación libera disponibilidad y conserva el historial.
5. Desactivar pacientes y médicos con historial en lugar de eliminarlos.
6. Comprobar permisos en el servidor y distinguir datos declarados de anotaciones profesionales.

## Primeros pasos

1. Leer [la guía de entorno](docs/entorno.md).
2. Revisar [el modelo](docs/modelo/README.md).
3. Especificar los casos de uso del [catálogo inicial](docs/casos-de-uso/README.md).
4. Trabajar en ramas cortas y revisar los cambios según [CONTRIBUTING.md](CONTRIBUTING.md).

Consultar [la guía de estructura](docs/estructura.md) antes de agregar archivos y [el índice de casos de uso](docs/casos-de-uso/README.md) para encontrar especificaciones, dependencias y evidencias.

## CU-06: horarios y bloqueos

- **Horario semanal:** médico activo al crear/reactivar; día de 1 (lunes) a 7 (domingo), horas dentro del mismo día, turno entero de 1 a 480 minutos y vigencia desde una fecha hasta otra opcional. La franja debe dividirse en turnos completos y la vigencia debe incluir al menos una fecha del día elegido. La fecha final es inclusiva.
- **Varias franjas:** permite mañana y tarde, vigencias diferentes y límites de horas adyacentes. PostgreSQL rechaza solapamientos activos del mismo médico únicamente cuando comparten día, horas y alguna fecha real de atención. Un rango de índices de semanas representa las ocurrencias del día para evitar falsos conflictos entre vigencias que solo coinciden en otros días.
- **Bloqueos y ausencias:** periodos de fecha/hora con motivo administrativo de hasta 255 caracteres; no pueden superponerse entre sí para un mismo médico. Se pueden registrar sobre franjas semanales sin eliminarlas: CU-07 deberá excluir los turnos afectados. Se usan intervalos `[inicio, fin)` y zona `America/La_Paz`.
- **Conservación:** no existe DELETE. La edición conserva médico y estado; las activaciones/desactivaciones requieren confirmación. Los formularios incluyen firma de versión para rechazar cambios desactualizados. No se cancelan ni reprograman citas automáticamente.
- **Citas existentes:** si un horario tiene citas, se conservan su día, franja, duración y fecha inicial. Solo se permite ajustar la vigencia final si mantiene todas las citas pendientes. No se puede desactivar un horario con citas pendientes ni crear/reactivar/mover un bloqueo sobre una cita pendiente.
- **Seguridad y concurrencia:** sesión y permiso efectivo en rutas y servicio, CSRF, salidas escapadas y transacciones con bloqueo de la fila del médico. Restricciones PostgreSQL comprueban intervalos válidos y evitan solapamientos incluso al omitir el servicio. La migración no corrige ni elimina datos previos: debe resolver un operador cualquier intervalo inválido o superpuesto que impida aplicarla.
- **Contrato para CU-07/CU-08:** antes de reservar o mover una cita, la transacción debe bloquear la misma fila `medicos` con `lockForUpdate`, volver a comprobar médico/especialidad/asignación activos, horario y vigencia, bloqueos y citas existentes, y solo entonces guardar. Los estados y columnas de `citas_medicas` no se modificaron en CU-06.

Código: `sistema/backend/routes/horarios.php`, controladores en `app/Http/Controllers/Horarios`, validaciones en `app/Http/Requests/Horarios`, servicio `app/Services/Horarios/ScheduleManagement.php`, modelos `HorarioAtencion` e `IndisponibilidadMedico`, y vistas en `sistema/frontend/views/horarios`. Se reutilizan estilos y componentes de listados adaptables; no se agregan dependencias.

Pruebas desde `sistema/backend`: `php artisan test tests/Feature/Horarios --compact`. Incluyen dos procesos sincronizados que intentan crear el mismo horario, tanto mediante el servicio como directamente en SQL; solo uno guarda. La suite usa exclusivamente PostgreSQL en `clinica_testing`, con datos ficticios y limpieza de las filas creadas para concurrencia. La revisión visual autenticada y los diagramas formales de CU-06 quedan pendientes.

## Entregas según el enunciado

- Primer avance: 24/10/2026, 23:59, al menos 50 % con documentación e implementación.
- Segunda entrega: el escrito indica «domingo 07/11/2026»; existe una contradicción entre día y fecha. Confirmar con la docente. Planificar la entrega antes del 07/11 a las 23:59 mientras se aclara.
- Defensa: 09/11/2026, 07:00. Horario de Bolivia.

Ambas entregas son obligatorias. El repositorio, las ramas y la documentación no acreditan por sí solos un porcentaje de implementación.

## CU-07: disponibilidad y reserva

- **Actores:** recepción/administración con `citas.gestionar` seleccionan una ficha activa; no se exige que el paciente tenga cuenta. Quien tiene únicamente `citas.propias` reserva para su propia ficha activa vinculada y debe verificar su correo. Si reúne ambos permisos se utiliza el flujo de gestión.
- **Entrada:** portada → `/reservar` → inicio de sesión → `/citas`. La continuación del acceso utiliza un destino interno fijo, sin aceptar URLs de retorno del cliente. El menú y el inicio enlazan al módulo.
- **Disponibilidad:** especialidad, médico opcional y fecha local; máximo 30 días hacia adelante, configurable en `config/citas.php`. Se generan turnos completos desde CU-06 y se excluyen horas pasadas, bloqueos activos y citas que crucen el intervalo del médico o del paciente. Médico, especialidad, asignación y horario deben estar activos y vigentes. Una consulta GET no aparta turnos.
- **Revisión:** muestra paciente, especialidad, médico, fecha y horas antes de guardar. Emite un token cifrado, vinculado al actor y al turno, que vence a los 30 minutos. El cliente no determina el final, el estado, el creador ni el código.
- **Reserva:** transacción que bloquea primero la fila del médico y después la del paciente, revalida permisos y disponibilidad, toma un bloqueo compartido de la especialidad y guarda cita más auditoría `creacion` en `cambios_cita`. Estado inicial `programada` y código `CIT-000001` basado en su ID. Reintentar el mismo token devuelve la misma cita sin otra auditoría; el resultado existente se puede recuperar aunque venza la revisión, siempre que conserve autorización y ficha activa.
- **PostgreSQL:** se conserva la exclusión de cruces por médico y se agrega otra por paciente, ambas con intervalos `[inicio, fin)`. Solamente `cancelada` e `inasistencia` liberan el intervalo. Se agrega `CHECK (fin > inicio)` y `reserva_token` UUID nullable único, compatible con las citas anteriores. No se cambian los estados existentes.
- **Contrato para CU-08/CU-09/CU-10:** cualquier modificación de disponibilidad debe respetar los bloqueos. Al mover una cita entre médicos, adquirir las filas de ambos médicos en orden ascendente de ID, después la del paciente y finalmente la cita; revalidar horario, especialidad, bloques y cruces dentro de la transacción. No borrar ni reutilizar `reserva_token`. CU-07 no implementa reprogramación, cancelación, confirmación, historial ni agenda médica.
- **Comprobante:** `/citas/{id}/comprobante`, accesible solo al propietario con permiso vigente o personal con `citas.gestionar`. Permite imprimir o guardar PDF desde el navegador; no envía correos ni exige SMTP para registrar la reserva.
- **Interfaz:** paneles y formularios compartidos, tarjetas fluidas, texto largo ajustable y estilos de impresión. Se revisaron en navegador las tres vistas renderizadas por las pruebas con datos ficticios largos a 320, 360, 390, 430, 768, 1024 y 1440px, sin desbordamiento horizontal. Esa revisión usa HTML renderizado en el esquema de pruebas; la interacción completa autenticada en navegador, el zoom nativo al 200%, la impresión/PDF y los diagramas formales siguen pendientes.
- **Pruebas:** `php artisan test tests/Feature/Citas --compact`. Incluyen permisos, ficha vinculada, verificación, manipulación y vencimiento del token, doble envío, rollback de auditoría, datos largos y seis carreras con dos procesos: mismo médico, mismo paciente con médicos distintos, mismo token, bloqueo, desactivación de horario y exclusión del paciente mediante SQL directo. Todo ocurre en `clinica_testing`; las pruebas no cargan pacientes ni agendas ficticias en la base operativa.

Para repetir la revisión visual, `CU07_RENDER_DIR` permite exportar las tres respuestas HTML del test `test_pages_keep_long_patient_and_doctor_data_and_accessible_actions` a una carpeta temporal. Sus recursos esperan una copia de `public/build` en `build/` de esa carpeta. Son archivos de prueba, no pantallas públicas ni datos de la base operativa.
