# Preparación de defensa

## CU-03 — Gestionar especialidades médicas

1. **¿Por qué guardamos nombre y nombre normalizado?** El nombre conserva la presentación elegida; la clave normalizada permite comparar sin diferencias de tildes, mayúsculas o espacios. PostgreSQL tiene un UNIQUE sobre esa clave.
2. **¿Qué pasa si dos administradores registran el mismo nombre al mismo tiempo?** La validación inicial puede aprobar ambos formularios, pero la restricción única solo permite uno. La otra transacción se revierte y recibe un mensaje comprensible.
3. **¿Por qué desactivamos en lugar de eliminar?** La especialidad puede estar vinculada a médicos y citas. Cambiar `activo` conserva esas referencias y permite reactivarla.
4. **¿Ocultar el botón es suficiente para proteger la operación?** No. Todas las rutas exigen sesión y permiso efectivo; las escrituras también verifican el permiso en el servicio y requieren CSRF.
5. **¿Cómo evitamos que una edición antigua reemplace otra reciente?** El formulario incluye una firma de la versión leída. Dentro de una transacción bloqueamos la fila y comparamos esa firma antes de actualizar; si cambió, pedimos recargar.

La continuidad con Laravel fue confirmada por el usuario. Las referencias históricas de CU-02/CU-04 difieren de la numeración de la imagen actual; consultar el catálogo antes de preparar las diapositivas.
