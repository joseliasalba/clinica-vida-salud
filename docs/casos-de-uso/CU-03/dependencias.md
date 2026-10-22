# CU-03 — Código y dependencias

- Rutas: `sistema/backend/routes/web.php` (portada) y `routes/auth.php` (registro y verificación).
- Controladores: `app/Http/Controllers/Pacientes/RegistrationController.php` y `Auth/VerificationController.php`.
- Validación: `app/Http/Requests/Pacientes/RegisterPatientRequest.php`, que reutiliza `SavePatientRequest`, `PasswordPolicy` y `UniqueUserIdentity`.
- Transacción: `app/Services/Pacientes/PatientSelfRegistration.php`. Comparte el bloqueo de administración de roles para no asignar un rol que se desactive concurrentemente desde CU-02.
- Persistencia: `usuarios`, `pacientes`, `usuario_rol`; migración `2026_10_22_170726_add_email_verified_at_to_usuarios_table.php`. Se conservan los índices únicos existentes.
- Correo: verificación nativa de Laravel en `User`, mensaje en `AppServiceProvider`, transporte en la configuración de correo existente.
- Datos públicos de demostración: `sistema/backend/config/clinica.php`. Sustituir valores y desactivar `datos_demostracion` al contar con información confirmada.
- Vistas: `sistema/frontend/views/public/home.blade.php`, `pacientes/register.blade.php`, `auth/verify-email.blade.php`, enlaces en login y panel privado.
- Estilos: `sistema/frontend/css/inicio/public.css` y puntos de adaptación compartidos en `css/shared/responsive.css`.
- Pruebas: `sistema/backend/tests/Feature/PublicPortalTest.php` y `Pacientes/PatientSelfRegistrationTest.php`.

CU-01 proporciona sesión; CU-02 roles y permisos; CU-04 la ficha administrativa. CU-07 implementará disponibilidad, reserva y emisión de tickets. El despliegue público y SMTP real no son requisitos para probar CU-03 localmente.
