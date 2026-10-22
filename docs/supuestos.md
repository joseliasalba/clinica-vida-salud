# Decisiones y supuestos vigentes

## CU-03 — 22/10/2026

1. **Confirmado por el usuario:** continuar con Laravel existente; esta decisión reemplaza la exigencia anterior de PHP sin framework para este desarrollo.
2. **Confirmado por la imagen del usuario:** CU-03 = Gestionar especialidades médicas; CU-07 = Consultar disponibilidad médica; CU-08 = Consultar agenda de citas. El catálogo anterior usa otra numeración; conservar las referencias históricas hasta conciliarlas con el equipo.
3. **Decisión de implementación:** reutilizar `medicos.gestionar`, que ya abarca especialidades y se asigna inicialmente al administrador; no agregar permisos o migraciones.
4. **Decisión de implementación:** nombre y descripción hasta 255 caracteres, conforme al esquema actual. Nombres únicos también entre inactivos, ignorando tildes, mayúsculas y espacios redundantes.
5. **Decisión de implementación:** alta activa, cambio de estado separado con pantalla de confirmación y sin borrado; no se modifican las relaciones existentes.
6. **Decisión de implementación:** conservar firma de versión y bloqueo de fila siguiendo el patrón de pacientes, para evitar sobrescribir una edición concurrente.
7. **Referencia revisada:** el archivo StarUML entregado en Downloads contiene ESPECIALIDAD y MEDICO_ESPECIALIDAD compatibles con esos campos. Difiere del archivo del repositorio; no se reemplaza el modelo compartido automáticamente.
8. **Pendiente:** contraste con el enunciado completo, aceptación del equipo y conciliación global de códigos CU. La implementación de este módulo usa RF04 de la guía y el caso de uso de la imagen.
