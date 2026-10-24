# Decisiones y supuestos vigentes

## CU-08, CU-09, CU-10 y CU-12 — Lucas — 23/10/2026

1. El último reparto del usuario asigna a Lucas reprogramación, cancelación, asistencia e historial; a Elías médicos, horarios, disponibilidad/reserva y agenda. Estos módulos operan sobre citas existentes; no implementan el registro de una cita nueva.
2. Se conserva Laravel y el esquema existente. `completada` se muestra como «Atendida»; `atendida` histórica también se reconoce. `en_curso` existente solo puede pasar a completada.
3. La máquina de estados única está en `CitaWorkflow::TRANSITIONS`. Programada puede confirmarse, atenderse, cancelarse o marcarse inasistencia. Confirmada puede atenderse, cancelarse o marcarse inasistencia. Atendida, cancelada e inasistencia son terminales; reabrir queda fuera de estos cuatro CU.
4. El personal con `citas.gestionar` puede modificar citas; el médico con `agenda.propia` solo registra estado de sus propias citas. Atendida/inasistencia requieren que haya llegado el inicio según el servidor en Bolivia.
5. Se puede reprogramar a otro médico activo de la misma especialidad. Se conservan paciente, especialidad y estado. Médico, horario y turno se revalidan dentro de la transacción, con firma de versión y bloqueo.
6. Para CU-12, personal con `pacientes.gestionar` o `citas.gestionar` consulta fichas; el paciente con `citas.propias` solo accede a su ficha vinculada. No se exponen motivos ni datos clínicos. El acceso del médico a agenda completa corresponde a CU-11.
7. La restricción PostgreSQL existente excluye canceladas e inasistencias del solape. No se cambia su definición sin acuerdo. La reprogramación trata toda cita no cancelada como ocupada, incluidas inasistencias; CU-07 conserva su política de liberar canceladas e inasistencias. CU-08 aplica el criterio más conservador de bloquear toda cita no cancelada. Ambos bloquean médico y paciente antes de guardar y PostgreSQL impide cruces de ambos recursos.
8. La base habitual continúa requiriendo sus credenciales locales correctas. Pruebas y vista previa usan una instancia temporal con datos ficticios; no acreditan despliegue con la base habitual ni aceptación académica.

## CU-05 — Médicos y especialidades — 22/10/2026

1. **Confirmado por el usuario:** continuar con Laravel existente; esta decisión reemplaza la exigencia anterior de PHP sin framework para este desarrollo.
2. **Confirmado por el usuario:** mantener nuestra numeración original: CU-03 = Registrarse como paciente; CU-04 = Registrar y gestionar pacientes en recepción; CU-05 = Gestionar médicos y especialidades; CU-06 = Configurar horarios; CU-07 = Consultar disponibilidad y reservar cita; CU-11 = Consultar agenda médica. El caso «Gestionar médicos», identificado como CU-04 en la referencia de chore, pertenece a nuestro CU-05. Especialidades forma parte del mismo CU-05.
3. **Decisión de implementación:** reutilizar `medicos.gestionar`, que ya abarca especialidades y se asigna inicialmente al administrador; no agregar permisos o migraciones.
4. **Decisión de implementación:** nombre y descripción hasta 255 caracteres, conforme al esquema actual. Nombres únicos también entre inactivos, ignorando tildes, mayúsculas y espacios redundantes.
5. **Decisión de implementación:** alta activa, cambio de estado separado con pantalla de confirmación y sin borrado; no se modifican las relaciones existentes.
6. **Decisión de implementación:** conservar firma de versión y bloqueo de fila siguiendo el patrón de pacientes, para evitar sobrescribir una edición concurrente.
7. **Referencia revisada:** el archivo StarUML entregado en Downloads contiene ESPECIALIDAD y MEDICO_ESPECIALIDAD compatibles con esos campos. Difiere del archivo del repositorio; no se reemplaza el modelo compartido automáticamente.
8. **Estado actualizado:** médicos (RF03) y especialidades (RF04) implementados. El vínculo con cuenta es opcional y explícito, solo para cuentas activas con permiso de agenda propia; no se crean usuarios ni se alteran roles. La ficha requiere especialidades activas y usa registro profesional único normalizado. Pendientes: contraste con el enunciado completo, aceptación del equipo, revisión visual y diagramas del submódulo de médicos. Horarios y reservas están incorporados en CU-06 y CU-07 desde la rama compartida.
