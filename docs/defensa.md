# Preparación de defensa

## CU-05 — Gestionar médicos y especialidades: especialidades

1. **¿Por qué guardamos nombre y nombre normalizado?** El nombre conserva la presentación elegida; la clave normalizada permite comparar sin diferencias de tildes, mayúsculas o espacios. PostgreSQL tiene un UNIQUE sobre esa clave.
2. **¿Qué pasa si dos administradores registran el mismo nombre al mismo tiempo?** La validación inicial puede aprobar ambos formularios, pero la restricción única solo permite uno. La otra transacción se revierte y recibe un mensaje comprensible.
3. **¿Por qué desactivamos en lugar de eliminar?** La especialidad puede estar vinculada a médicos y citas. Cambiar `activo` conserva esas referencias y permite reactivarla.
4. **¿Ocultar el botón es suficiente para proteger la operación?** No. Todas las rutas exigen sesión y permiso efectivo; las escrituras también verifican el permiso en el servicio y requieren CSRF.
5. **¿Cómo evitamos que una edición antigua reemplace otra reciente?** El formulario incluye una firma de la versión leída. Dentro de una transacción bloqueamos la fila y comparamos esa firma antes de actualizar; si cambió, pedimos recargar.

La continuidad con Laravel fue confirmada por el usuario. La numeración acordada es CU-02 para usuarios/roles/permisos, CU-03 para autorregistro, CU-04 para pacientes en recepción, CU-05 para médicos y especialidades, CU-06 para horarios, CU-07 para disponibilidad y reserva, y CU-11 para agenda médica. Esta defensa cubre el submódulo de especialidades; la gestión de médicos sigue pendiente en esta rama.
