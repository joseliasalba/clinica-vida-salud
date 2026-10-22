# Decisiones y supuestos vigentes

## CU-05 — Médicos y especialidades — 22/10/2026

1. **Confirmado por el usuario:** continuar con Laravel existente; esta decisión reemplaza la exigencia anterior de PHP sin framework para este desarrollo.
2. **Confirmado por el usuario:** mantener nuestra numeración original: CU-03 = Registrarse como paciente; CU-04 = Registrar y gestionar pacientes en recepción; CU-05 = Gestionar médicos y especialidades; CU-06 = Configurar horarios; CU-07 = Consultar disponibilidad y reservar cita; CU-11 = Consultar agenda médica. El caso «Gestionar médicos», identificado como CU-04 en la referencia de chore, pertenece a nuestro CU-05. Especialidades forma parte del mismo CU-05.
3. **Decisión de implementación:** reutilizar `medicos.gestionar`, que ya abarca especialidades y se asigna inicialmente al administrador; no agregar permisos o migraciones.
4. **Decisión de implementación:** nombre y descripción hasta 255 caracteres, conforme al esquema actual. Nombres únicos también entre inactivos, ignorando tildes, mayúsculas y espacios redundantes.
5. **Decisión de implementación:** alta activa, cambio de estado separado con pantalla de confirmación y sin borrado; no se modifican las relaciones existentes.
6. **Decisión de implementación:** conservar firma de versión y bloqueo de fila siguiendo el patrón de pacientes, para evitar sobrescribir una edición concurrente.
7. **Referencia revisada:** el archivo StarUML entregado en Downloads contiene ESPECIALIDAD y MEDICO_ESPECIALIDAD compatibles con esos campos. Difiere del archivo del repositorio; no se reemplaza el modelo compartido automáticamente.
8. **Estado actualizado:** médicos (RF03) y especialidades (RF04) implementados. El vínculo con cuenta es opcional y explícito, solo para cuentas activas con permiso de agenda propia; no se crean usuarios ni se alteran roles. La ficha requiere especialidades activas y usa registro profesional único normalizado. Pendientes: contraste con el enunciado completo, aceptación del equipo, revisión visual y diagramas del submódulo de médicos. Horarios y reservas se implementarán después, en CU-06 y CU-07.
