# Catálogo de casos de uso

## Numeración acordada

El usuario confirmó el 22/10/2026 mantener la numeración original del proyecto. CU-03 corresponde al autorregistro de pacientes; CU-04, a la gestión de pacientes en recepción; CU-05, a médicos y especialidades; CU-06, a horarios; CU-07, a disponibilidad y reserva; y CU-11, a agenda médica. Esta decisión reemplaza el catálogo alternativo que se usó anteriormente en esta rama.

Los códigos CU son propios del proyecto y no sustituyen los RF del enunciado. Cada caso tiene una carpeta directa `CU-XX/` con `especificacion.md`, `dependencias.md` y `pruebas.md`; se usa `PLANTILLA.md` para la especificación. Los ciclos se indican aquí, sin anidar casos dentro de otros casos ni de carpetas de entregas.

| Código | Caso de uso | Actor principal | Fuente | Ciclo propuesto |
| --- | --- | --- | --- | --- |
| CU-01 | Iniciar y cerrar sesión | Usuario | RF01 | 1 |
| CU-02 | Administrar usuarios, roles y permisos | Administrador | RF01 y roles | 1 |
| CU-03 | Registrarse como paciente | Paciente | Perfil paciente y decisión del equipo | 1 |
| CU-04 | Registrar y gestionar pacientes en recepción | Recepción / administrador autorizado | RF02 | 1 |
| CU-05 | Gestionar médicos y especialidades | Administrador | RF03, RF04 | 1 |
| CU-06 | Configurar horarios de atención | Administrador | RF05 | 1 |
| CU-07 | Consultar disponibilidad y reservar cita | Paciente / recepción | RF06, RF07 | 1 |
| CU-08 | Reprogramar una cita | Recepción; alcance del paciente por definir | RF08 | 1 |
| CU-09 | Cancelar una cita | Recepción; alcance del paciente por definir | RF09 | 1 |
| CU-10 | Confirmar y registrar atención o inasistencia | Recepción / médico según permiso | RF10 | 1 |
| CU-11 | Consultar agenda médica | Médico / recepción según permiso | RF11 | 1 |
| CU-12 | Consultar historial de citas | Paciente / personal autorizado | RF12 | 1 |
| CU-13 | Generar reportes administrativos | Administrador / personal autorizado | RF13 | 2 |
| CU-14 | Actualizar perfil, salud declarada y contacto de emergencia | Paciente | Perfil paciente | 2 |
| CU-15 | Registrar consulta y tratamiento | Médico autorizado | Contexto y perfil clínico | 2 |
| CU-16 | Solicitar estudios y consultar/publicar resultados | Médico solicitante / paciente vinculado | Contexto de continuidad y decisión del equipo | 2 |
| CU-17 | Gestionar preferencias y recordatorios | Paciente / proceso programado | Perfil paciente | 2 |
| CU-18 | Pagar una consulta en línea | Paciente / pasarela de pago | Ampliación acordada por el equipo | Por definir |
| CU-19 | Gestionar reembolsos de consultas | Paciente / personal autorizado / pasarela | Ampliación acordada por el equipo | Publicado; prueba integral en nube pendiente |
| CU-20 | Recibir aviso de resultados de laboratorio disponibles | Paciente / servicio de correo | Ampliación acordada por el equipo | Implementado; entrega real pendiente de verificación |
| CU-21 | Inscribirse en una lista de espera para citas | Paciente / recepción | Ampliación acordada por el equipo | Implementado; verificación integral en nube pendiente |
| CU-22 | Registrar llegada y consultar turno de atención | Paciente / recepción / médico | Ampliación acordada por el equipo | Por definir |

La asignación por ciclo es propuesta, porque el plan detallado del ZIP no se ha podido leer. El bloqueo de citas duplicadas y la autorización son reglas transversales, no pasos opcionales de la interfaz.

Orden sugerido para comenzar: CU-01, CU-03/CU-04, CU-05, CU-06 y CU-07. El caso CU-07 debe incluir reservas simultáneas en sus criterios de aceptación.

## Documentación y estado

| Caso | Módulo | Especificación | Dependencias y código | Evidencia | Estado |
| --- | --- | --- | --- | --- | --- |
| CU-01 | Auth | [Especificación](CU-01/especificacion.md) | [Trazabilidad](CU-01/dependencias.md) | [Pruebas](CU-01/pruebas.md) | Implementado con pruebas automáticas; revisión en navegador pendiente |
| CU-01.1 | Auth | [Especificación](CU-01.1/especificacion.md) | [Trazabilidad](CU-01.1/dependencias.md) | [Pruebas](CU-01.1/pruebas.md) | Implementado con pruebas automáticas; SMTP real y revisión en navegador pendientes |
| CU-02 | Auth | [Especificación](CU-02/especificacion.md) | [Trazabilidad](CU-02/dependencias.md) | [Pruebas](CU-02/pruebas.md) | Usuarios, roles y permisos implementados y probados; revisión visual pendiente |
| CU-03 | Pacientes | [Especificación](CU-03/especificacion.md) | [Código y dependencias](CU-03/dependencias.md) | [Pruebas](CU-03/pruebas.md) | Portada pública y autorregistro implementados; revisión visual y SMTP real pendientes |
| CU-04 | Pacientes | [Especificación](CU-04/especificacion.md) | [Código y dependencias](CU-04/dependencias.md) | [Pruebas](CU-04/pruebas.md) | Fichas administrativas implementadas y probadas; revisión visual pendiente |
| CU-05 | Médicos y especialidades | [Especificación](CU-05/especificacion.md) | [Trazabilidad](CU-05/dependencias.md) | [Pruebas](CU-05/pruebas.md) | Médicos y especialidades implementados; revisión visual de médicos pendiente |
| CU-06 | Horarios y bloqueos | [Reglas implementadas](../../README.md#cu-06-horarios-y-bloqueos) | [Servicio](../../sistema/backend/app/Services/Horarios/ScheduleManagement.php) | [Pruebas](../../sistema/backend/tests/Feature/Horarios/ScheduleManagementTest.php) | Implementado; revisión visual autenticada pendiente |
| CU-07 | Citas | [Reglas implementadas](../../README.md#cu-07-disponibilidad-y-reserva) | [Servicio](../../sistema/backend/app/Services/Citas/AppointmentBooking.php) | [Pruebas](../../sistema/backend/tests/Feature/Citas/AppointmentBookingTest.php) | Implementado; revisión de vistas de prueba en navegador, flujo autenticado e impresión pendientes |
| CU-08 | Citas · Lucas | [Especificación](CU-08/especificacion.md) | [Trazabilidad](CU-08/dependencias.md) | [Pruebas](CU-08/pruebas.md) | Reprogramación implementada, probada y recorrida en navegador |
| CU-09 | Citas · Lucas | [Especificación](CU-09/especificacion.md) | [Trazabilidad](CU-09/dependencias.md) | [Pruebas](CU-09/pruebas.md) | Cancelación con motivo y auditoría implementada y probada |
| CU-10 | Citas · Lucas | [Especificación](CU-10/especificacion.md) | [Trazabilidad](CU-10/dependencias.md) | [Pruebas](CU-10/pruebas.md) | Confirmación, atención e inasistencia implementadas y probadas |
| CU-12 | Citas · Lucas | [Especificación](CU-12/especificacion.md) | [Trazabilidad](CU-12/dependencias.md) | [Pruebas](CU-12/pruebas.md) | Historial con propiedad, filtros y paginación implementado y probado |
| CU-16 | Estudios | [Alcance e integración](#cu-16-estudios-y-resultados) | [Servicio](../../sistema/backend/app/Services/Estudios/StudyManagement.php) | [Pruebas](../../sistema/backend/tests/Feature/Estudios/StudyManagementTest.php) | Solicitudes, borradores, publicación, retiro y PDF privado implementados; integración con pantallas CU-15 pendiente |
| CU-17 | Recordatorios | [Alcance y ejecución](#cu-17-preferencias-y-recordatorios) | [Servicio](../../sistema/backend/app/Services/Recordatorios/ReminderManagement.php) | [Pruebas](../../sistema/backend/tests/Feature/Recordatorios/ReminderManagementTest.php) | Preferencias propias, programación y envío por correo implementados; activación del scheduler y entrega real pendientes |
| CU-18 | Pagos | [Decisiones y Stripe sandbox](#cu-18-pagos-de-consulta-con-stripe-sandbox) | [Tarifas](../../sistema/backend/app/Services/Especialidades/SpecialtyManagement.php) | [Pruebas de tarifas](../../sistema/backend/tests/Feature/Especialidades/SpecialtyFeeTest.php) | Tarifas, retenciones y Checkout sandbox implementados; webhook público y prueba interactiva pendientes |

Reparto vigente de la imagen del 23/10/2026: Elías CU-05/CU-06/CU-07/CU-11; Lucas CU-08/CU-09/CU-10/CU-12. Estos cuatro últimos operan sobre citas existentes y no crean reservas nuevas. Los resultados integrados están en [evidencias Lucas](../pruebas/Lucas/RESULTADOS.md). Se conservan diagramas editables de cuatro páginas por CU en `docs/diagramas/`; la revisión de su apariencia en draw.io y la exportación PNG siguen pendientes.


La copia integra el autorregistro CU-03, la portada pública, el menú lateral, la adaptación móvil, CU-05, CU-06, el flujo de disponibilidad/reserva del CU-07, los CU-08/CU-09/CU-10/CU-12 de Lucas y CU-16/CU-17. CU-11 sigue pendiente. Los CU-12 a CU-15 están siendo trabajados por el compañero; estas implementaciones no modifican sus flujos. Los demás casos no incluidos están pendientes de especificación e implementación. Tener tablas o permisos iniciales no significa que esos casos estén terminados.

## CU-16: estudios y resultados

**DEFINIDO por el equipo:** el médico solicitante carga y publica los resultados de sus propias consultas. No se crea un rol de laboratorio. El administrador y recepción no reciben acceso clínico por defecto. El paciente necesita sesión, correo verificado, permiso efectivo y una ficha vinculada a su cuenta; nunca escoge otra identidad.

- Entrada: menú «Estudios solicitados» para médicos y «Mis estudios y resultados» para pacientes, en `/estudios`.
- Solicitud: consulta existente, nombre de estudio e indicaciones opcionales. Solo se ofrecen consultas del profesional activo vinculado, con cita en atención o atendida, fecha no futura e identidad consistente entre cita e historia clínica.
- Seguimiento: solicitado → en proceso → completado al publicar. Una solicitud abierta se puede cancelar con motivo.
- Resultado: descripción o PDF de hasta 2 MB. El borrador solo es visible para el médico solicitante. Publicar identifica al usuario y la fecha; retirar exige motivo, oculta inmediatamente el resultado al paciente y devuelve el estudio a «en proceso» para corregirlo. Un resultado publicado no se sobrescribe directamente.
- Acceso a archivos mediante controlador autenticado; el disco `estudios` no tiene URL pública. Las rutas no aceptan una ruta de archivo suministrada por el cliente.
- Cambios transaccionales con bloqueo de solicitud, comprobación de formulario vigente, un resultado por solicitud y registro en `eventos_estudio`. Si falla una carga, se revierte la operación y se elimina el archivo nuevo.
- **CU-20:** los correos de resultados disponibles requieren consentimiento independiente y correo verificado.

**Contrato con CU-15:** se conservan las tablas `consultas_medicas`, `historias_clinicas` y `citas_medicas`; este módulo no crea consultas ni modifica su flujo. CU-15 puede enlazar con `route('estudios.create', ['consulta_id' => $consulta->id])`. La autorización se verifica nuevamente al guardar. Sin consultas registradas, la pantalla muestra un estado vacío; no se crean atenciones ficticias en la base de desarrollo.

Para instalar el módulo en otra copia, ejecutar desde `sistema/backend`:

```text
php artisan migrate --no-interaction
php artisan db:seed --class=RolesPermisosSeeder --no-interaction
```

Los permisos nuevos son `estudios.solicitar` y `estudios.publicar` para el rol médico, y `estudios.propios` para el rol paciente. Se mantienen los permisos existentes y las desactivaciones previas.

**Verificación observada (06/11/2026):** 36 pruebas del módulo y de permisos, navegación e interfaz adaptable aprobadas, con 1154 aserciones. Suite completa posterior aprobada: 302 pruebas y 3822 aserciones. Compilación de frontend aprobada. Las mediciones de ancho con vistas renderizadas y datos ficticios no detectaron desbordamiento horizontal en el listado a 320, 360, 390, 430, 768, 1024 y 1440 px; solicitud, borrador, resultado publicado y vista paciente también se midieron en esos siete anchos. La captura visual y el zoom al 200 % siguen pendientes; la herramienta de navegador no pudo capturar imágenes. El recorrido autenticado completo se probó mediante HTTP automatizado.

## CU-17: preferencias y recordatorios

**Decisiones de implementación:** recordatorios por correo con anticipación de 2, 24 o 48 horas. No se implementan SMS, WhatsApp ni push. Los pacientes sin preferencias guardadas comienzan con recordatorios desactivados; no se altera el registro ni el perfil clínico del CU-14. Se conservan otras claves existentes de privacidad.

- Entrada: «Mi cuenta → Preferencias y recordatorios», en `/mi-cuenta/recordatorios`. Solo la cuenta activa, con correo verificado, permiso efectivo `recordatorios.propios` y ficha activa vinculada puede leer o cambiar sus preferencias. No se admite seleccionar otro paciente.
- El correo se toma de `usuarios.correo`, no de un destinatario escrito en un formulario. Se puede optar por incluir médico y especialidad; la opción inicial incluye solo fecha, hora y enlace autenticado. No se incluyen diagnósticos, tratamientos ni resultados.
- Se procesan citas programadas o confirmadas que aún no comenzaron. Al activar el servicio dentro de la anticipación elegida, el recordatorio se intenta en la siguiente ejecución. Los mensajes de verificación y recuperación de contraseña no dependen de esta preferencia.
- La programación usa el inicio vigente de la cita. El proceso vuelve a comprobar preferencias, identidad y estado antes de enviar; los recordatorios pendientes de una fecha anterior se omiten si la cita se reprogramó. Cambiar preferencias no puede retirar un correo que ya fue enviado.
- La combinación cita/inicio/canal es única y los intentos se reservan con bloqueo de filas. Después de reservar un envío, los fallos o interrupciones no producen reintentos automáticos: un proveedor podría haber aceptado un correo aunque la aplicación no recibiera respuesta.
- El historial del paciente distingue pendiente, envío en curso/sin confirmar, aceptado por el servicio, fallido, omitido y simulado. «Aceptado» no acredita llegada a la bandeja. Los mailers `log` y `array` registran una simulación.

**Instalación:** aplicar migraciones y ejecutar `RolesPermisosSeeder`, igual que en CU-16. El permiso nuevo corresponde únicamente al rol paciente por defecto.

**Ejecución:** el comando `php artisan clinica:enviar-recordatorios` programa y procesa los recordatorios. La opción `--simular` cuenta los que se programarían sin guardar ni enviar. Está registrado cada minuto en `routes/console.php`; para automatizarlo se necesita un scheduler activo, por ejemplo `php artisan schedule:work` en desarrollo o `schedule:run` desde el programador del servidor. No requiere un worker de colas separado.

Durante el desarrollo se probaron notificaciones simuladas y se ejecutó la simulación local; no se enviaron recordatorios reales ni se inició un proceso permanente de envíos. CU-20 conserva su alcance separado de avisar cuando existan resultados publicados.

**Verificación observada (06/11/2026):** 52 pruebas de CU-17, CU-16, permisos, navegación e interfaz adaptable aprobadas, con 1260 aserciones. La suite general anterior a la última prueba de reactivación ejecutó 317 casos: 315 aprobados y dos fallos por deadlock de PostgreSQL en `AppointmentConcurrencyTest` (dataset #5) y `ScheduleConcurrencyTest` (dataset #1), en código ajeno a CU-17; ambos volvieron a fallar en la repetición dirigida. Compilación, migración, permisos y registro del comando en el scheduler comprobados. Las mediciones en navegador sobre HTML renderizado con datos ficticios no detectaron desbordamiento horizontal a 320, 360, 390, 430, 768, 1024 y 1440 px; la revisión de capturas y del zoom al 200 % continúa pendiente. El recorrido autenticado y las notificaciones se verificaron con pruebas HTTP y notificaciones simuladas.

## CU-18: pagos de consulta con Stripe sandbox

**DEFINIDO por el usuario (06/11/2026):** usar Stripe en su entorno de pruebas, cobrar por especialidad y retener temporalmente el cupo hasta verificar el pago. Se consultó el planificador del plugin oficial de Stripe. Se usa Checkout alojado, modo pago único y comprobante de Stripe generado después del pago (`invoice_creation`), sin cobrar una segunda factura. BNB queda fuera de esta implementación.

**Tarifas:** importe opcional en bolivianos en creación/edición y listado de especialidades. Se guarda como entero de centavos, entre 0.01 y 999999.99 Bs, sin floats. Vacío significa sin configurar, nunca gratuito. Las especialidades existentes conservan tarifa nula; hay que configurar el precio antes de reservar con pago. El permiso `medicos.gestionar` controla la edición y cada cambio deja actor, fecha y valores anterior/nuevo en `cambios_tarifa_especialidad`. La reserva guarda una copia del precio; un cambio posterior no modifica el importe pendiente.

**QR con revisión manual:** el administrador autorizado puede cargar una imagen PNG o JPEG de hasta 2 MB; se guarda en PostgreSQL y cada pago conserva la versión seleccionada. El paciente informa un número de operación y recepción verifica por separado en la cuenta bancaria el importe y el destinatario antes de aprobar o rechazar. El reporte por sí solo no confirma la cita. Una vez reportado, el cupo permanece retenido hasta la revisión; un reporte tardío cuyo cupo se liberó queda en revisión, sin volver a ocuparlo. No se permite cambiar entre Stripe y QR después de iniciar un método y los números de operación no pueden duplicarse. Un QR asociado a una cuenta personal puede recibir dinero real. El código y su migración se publicaron en Vercel y Neon; queda pendiente cargar el QR y una prueba funcional en la nube.

**Flujo implementado:** con `PAGOS_HABILITADOS=true`, las nuevas reservas del paciente quedan en `pendiente_pago`. El cupo se conserva mediante los bloqueos de médico/paciente y las restricciones de solapamiento existentes. Hay 30 minutos para iniciar Checkout; al iniciarlo por primera vez se fija un plazo de 31 minutos, compatible con su mínimo de expiración y el tiempo de red. Los reintentos conservan el mismo cuerpo y clave de idempotencia. La reserva solo se confirma con una respuesta de Stripe que coincida en sesión, referencia, moneda BOB, importe y modo de prueba. El retorno del navegador no acredita un pago. Las reservas de recepción y las anteriores no se cobran retroactivamente.

**Estados y conciliación:** los pagos inciertos se consultan sin crear otro cobro. Las notificaciones requieren firma válida y se deduplican; las confirmaciones simultáneas producen una sola transición. Un vencimiento confirmado libera el cupo. Un pago recibido después de liberar la cita queda en revisión y nunca ocupa otro turno automáticamente; el reembolso pertenece a CU-19. Los intentos inciertos de más de 23 horas requieren conciliación manual para no reutilizar una clave de idempotencia caducada. El comando `clinica:conciliar-pagos` revisa pagos pendientes y está programado cada minuto cuando existe un scheduler en ejecución. En local puede ejecutarse de forma independiente.

**Interfaz y acceso:** menú «Mi cuenta → Mis pagos», detalle de importe/plazo/estado, inicio de Checkout, consulta de estado y acceso al comprobante de Stripe tras el pago. Cada paciente solo puede acceder a sus pagos. El comprobante de Stripe no se presenta como factura fiscal boliviana. No se envían datos clínicos a Stripe. Las claves están en el entorno local ignorado por Git; la plantilla incluye únicamente nombres y valores vacíos. Se utiliza el SDK oficial `stripe/stripe-php` 22.1.0, aprobado por el usuario.

**Verificación (06/11/2026):** pruebas de pagos, concurrencia, reservas, navegación y estructura adaptable: 53 aprobadas, 1307 aserciones y una prueba externa omitida en la ejecución ordinaria. La prueba externa se ejecutó por separado contra Stripe sandbox y aprobó 9 aserciones: autenticación, creación en BOB, repetición idempotente, consulta y expiración de Checkout. No se completó todavía un pago interactivo con tarjeta ni la generación real del comprobante. Revisión general: 339 aprobadas, una omitida y un fallo previo de deadlock en la prueba SQL concurrente de horarios. Pint y compilación frontend aprobados; migración local de pagos aplicada.

**Estado en la nube:** Stripe sandbox, el webhook de Checkout y devoluciones, las migraciones hasta CU-19 y una conciliación periódica mediante GitHub Actions ya están configurados. Se completó una reserva y un pago de prueba en Vercel. La conciliación revisa un pago por ejecución cada cinco minutos y puede retrasarse; no garantiza liberación inmediata. CU-16 todavía utiliza almacenamiento privado local y requiere adaptar su disco antes de cargar estudios en Vercel. La geometría móvil y el zoom de las pantallas nuevas quedan pendientes de inspección visual.

## CU-19: gestión de reembolsos

**Decisión del usuario:** revisión manual de cada solicitud; para pagos QR, recepción registra la solicitud y el administrador la aprueba o rechaza y registra la devolución una vez realizada fuera del sistema. Una cancelación no devuelve dinero automáticamente. El paciente puede solicitar la devolución de sus pagos Stripe elegibles o contactar a recepción para QR. Solo se aceptan pagos confirmados de citas canceladas o pagos tardíos en revisión. En este primer alcance se solicita el importe completo; no se implementaron devoluciones parciales.

**Implementación local:** cada solicitud guarda pago, importe, motivo, solicitante y eventos de auditoría; un índice impide solicitudes activas duplicadas. La aprobación requiere rol administrador activo y permiso efectivo. Para QR, el registro de devolución exige confirmación y una referencia de operación; no ejecuta una transferencia. Para Stripe, exclusivamente sandbox, la aprobación y la ejecución son pasos separados; el intento usa idempotencia estable, comprueba importe, moneda, transacción y estado en Stripe y conserva los intentos inciertos para consulta. El sistema acepta eventos firmados `refund.created`, `refund.updated` y `refund.failed`, y permite consultar manualmente el intento.

**Pendiente para la nube:** verificar una devolución completa desde el despliegue. El webhook de Stripe sandbox incluye los tres eventos de devolución. La implementación QR no verifica transferencias por sí sola, no ejecuta devoluciones bancarias y puede recibir dinero real si se configura una cuenta personal.

## CU-20: avisos de resultados

**Decisión del usuario:** solo se envían cuando el paciente activa esta preferencia; requiere un correo verificado. La preferencia es independiente de los recordatorios de citas y se aplica a publicaciones posteriores a la activación.

**Implementación:** la publicación encola el aviso en la misma transacción. El procesamiento comprueba nuevamente la vinculación del paciente, correo verificado, permisos, consentimiento y vigencia de la publicación; omite avisos de resultados retirados o sustituidos. El correo es genérico y enlaza al acceso de la cuenta sin incluir datos clínicos. Una reserva atómica impide envíos duplicados; los fallos y resultados inciertos no se reintentan automáticamente. Log y array se identifican como simulación.

**Operación:** la tarea de GitHub está preparada para una ejecución cada cinco minutos y procesa como máximo un aviso por ejecución; puede sufrir retrasos. La entrega real de CU-20 debe verificarse. CU-16 necesita almacenamiento persistente para archivos adjuntos en Vercel.

## CU-21: lista de espera

El paciente o recepción puede registrar una solicitud para una especialidad, fecha y médico opcional desde la consulta de turnos sin resultados. No se reserva un cupo ni se genera un pago. Recepción consulta las solicitudes por orden de inscripción, revisa manualmente la disponibilidad, contacta al paciente y registra el estado; la reserva sigue el flujo habitual. El paciente solo consulta o cancela sus propias solicitudes. Se evita más de una solicitud activa por paciente, especialidad y fecha. Las solicitudes de fechas pasadas se consultan filtrando por estado. No hay avisos ni asignación automáticos y la reserva no cierra automáticamente la solicitud.

## Convenciones de documentación

**Pruebas locales sin verificar correo (decisión del usuario, 06/11/2026):** `OMITIR_VERIFICACION_CORREO_LOCAL=true` permite acceder a reservas, estudios y preferencias sin verificar correo solo cuando `APP_ENV=local` y el despliegue no es Vercel. El valor predeterminado de la plantilla es `false`. No se modifica `email_verified_at` ni se omite la verificación del destinatario en el envío real de recordatorios. Se probaron los límites de entorno, el acceso local y las regresiones de reservas/CU-16/CU-17: 62 pruebas aprobadas, 516 aserciones.

- **DEFINIDO:** requisito con fuente identificada o decisión expresamente acordada; indicar cuál de las dos es.
- **PROPUESTO:** decisión aún no acordada.
- **PENDIENTE:** requisito por aclarar, código por implementar o verificación por realizar.

La [guía de estructura](../estructura.md) explica dónde colocar controladores, validaciones, modelos, vistas, recursos y pruebas de cada módulo. Las verificaciones pendientes se registran en el documento de pruebas de cada caso, sin presentarlas como aprobadas.

## Diagramas de CU-03 a CU-07

Disponibles los diagramas de casos de uso, comunicación, navegación y secuencia de cada caso: [modelo editable de Enterprise Architect y entregables](../modelo/enterprise-architect/CU03_CU07/README.md). Se contrastaron con el código implementado; su entrega no elimina las verificaciones funcionales pendientes indicadas arriba.
