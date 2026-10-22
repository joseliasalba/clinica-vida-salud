# CU-03 — Registrarse como paciente

**Módulo:** Pacientes. **Actor:** visitante sin sesión. **Ciclo:** 1.

## Alcance acordado

El usuario aprobó una portada pública de Clínica Vida Salud, registro de pacientes y acceso con usuario/correo y contraseña. Autorizó usar información institucional ficticia temporal; se identifica como demostración y se configura en `config/clinica.php`.

La portada está en `/`; `/inicio` conserva el panel privado. La portada presenta la clínica, especialidades de ejemplo, contacto y entradas al registro, login e información sobre tickets. La reserva de citas corresponde al CU-07 y se indica expresamente como pendiente.

## Flujo

1. El visitante selecciona «Crear mi cuenta» en la portada o el login.
2. Completa nombres, apellidos, nacimiento, teléfono, usuario, correo y contraseña confirmada. CI con complemento, sexo y dirección son opcionales, conservando el alcance de CU-04.
3. El servidor normaliza usuario/correo a minúsculas y CI a mayúsculas, conserva ceros iniciales y valida los datos. La contraseña no se transforma.
4. Una transacción crea la cuenta activa, asigna exclusivamente el rol paciente activo y crea la ficha administrativa vinculada con código `PAC-` e ID. No se crean citas ni historias clínicas.
5. El sistema solicita el correo de verificación y dirige al login. Si el envío falla, conserva la cuenta creada y explica cómo solicitar otro enlace.
6. El paciente inicia sesión. Su panel muestra el aviso de correo pendiente; el enlace firmado permite confirmarlo con la misma cuenta autenticada. También puede solicitar reenvío.

## Reglas y excepciones

- Reutilizar la política de contraseña de 8 a 128 caracteres y el hash configurado del sistema.
- Usuario y correo de acceso únicos; CI normalizado único entre fichas, incluidas las inactivas. CI vacío se guarda como `NULL`.
- Una ficha previa no se vincula automáticamente por CI, correo, nombre o nacimiento. Se requiere revisión por recepción; esta entrega no incorpora una herramienta de vinculación.
- Rol paciente inexistente o inactivo: rechazar el registro sin crear registros parciales.
- Proteger los POST con CSRF y limitar intentos de registro y reenvío. No aceptar rol, estado, código, vínculo, ID o verificación aportados por el visitante.
- Verificación: enlace firmado de 60 minutos, cuenta e identificación del correo coincidentes, sesión activa y reenvío limitado. Cambiar el correo desde administración invalida la verificación anterior.
- El panel actual permite consultar sus opciones sin bloquear las cuentas existentes por verificación. Al implementar CU-07 debe definirse y aplicarse la verificación como requisito para reservar.
- La entrega real por SMTP queda pendiente; el entorno local usa correo en logs. Confirmar correo no comprueba la identidad civil ni autoriza a vincular una ficha previa.

## Criterios de aceptación

Portada pública accesible; registro con cuenta/ficha/rol atómicos; login funcional; permisos administrativos denegados al nuevo paciente; duplicados y entradas inválidas rechazados sin altas parciales; contraseña no expuesta en HTML o entrada anterior; verificación y reenvío protegidos; información de ejemplo y reserva pendiente claramente identificadas.
