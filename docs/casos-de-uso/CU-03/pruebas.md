# CU-03 — Validación

## Pruebas automáticas

Resultado del 22/10/2026: batería completa aprobada, **110 pruebas y 1301 aserciones**. Compilación Vite correcta. Migración aplicada al entorno local; portada, registro y CSS responden HTTP 200. Estos resultados incluyen la regresión de los módulos previos y no equivalen a revisión visual ni a entrega SMTP real.

Integración posterior con `chore/entorno-desarrollo`, el mismo día: **124 pruebas y 1535 aserciones aprobadas**. Incluye las 14 pruebas de especialidades y el listado adaptado a los componentes móviles compartidos. Formato Pint y compilación Vite correctos; portada, registro y CSS responden HTTP 200. CU-07 sigue pausado y no se contabilizan sus borradores como funciones implementadas. La revisión visual de la integración y la entrega SMTP real siguen pendientes.

Desde `sistema/backend`, con el PHP del proyecto:

```powershell
php artisan test tests/Feature/PublicPortalTest.php tests/Feature/Pacientes/PatientSelfRegistrationTest.php --compact
```

Se usa PostgreSQL con el esquema aislado `clinica_testing` y transacciones. Las notificaciones se simulan: estas pruebas no envían correo real.

Cobertura: portada pública, rutas de acceso, escape de contenido institucional y datos de ejemplo; registro y vínculo correcto; política de contraseña y confirmación; validación de datos y normalización; rechazo de campos privilegiados; duplicados sin vinculación automática; rollback de cuenta/rol/ficha ante colisiones; rol desactivado; CSRF y límites de solicitudes; contraseña excluida de los datos anteriores; login del paciente y denegación de administración; enlace firmado con expiración/cuenta/correo; reenvío protegido; recuperación ante fallo del correo; cambio de correo que reinicia la verificación.

## Revisión manual pendiente

- Revisar portada, registro, mensajes, login y verificación en 320, 360, 390, 430, 768, 1024 y 1440px y zoom al 200%.
- Recorrer con teclado; comprobar foco visible, cabecera persistente, menú lateral y anclas bajo la cabecera.
- Probar verificación en un navegador con correo de desarrollo; comprobar la entrega real únicamente cuando se configure SMTP.
- Confirmar datos institucionales definitivos. El teléfono de demostración no es un contacto operativo.
- La reserva de citas y los diagramas de CU-03 no forman parte de estas verificaciones.

Las comprobaciones HTTP no acreditan geometría visual. No hay navegador disponible en esta sesión; la revisión visual queda pendiente.
