# Pruebas de CU-10

Archivo: `sistema/backend/tests/Feature/Citas/AsistenciaTest.php`. Se ejecuta contra el esquema PostgreSQL de pruebas aislado del proyecto y revierte los datos de cada caso. La ejecución se coordina con el resto de los CU para evitar migraciones concurrentes.

Cobertura implementada:

1. Sesión obligatoria y rechazo del rol paciente.
2. Confirmación por administrador y recepción con auditoría del actor.
3. Acceso del médico a su cita y rechazo de citas ajenas.
4. Registro de atendida/inasistencia desde una cita confirmada, con snapshots anterior y nuevo y observación.
5. Rechazo de cierres anticipados y opciones disponibles para citas futuras.
6. Cierre permitido exactamente a la hora de inicio en Bolivia.
7. Inmutabilidad de estados terminales y ausencia de botón de confirmación.
8. Desde «en curso» se permite únicamente «Atendida».
9. Estados inválidos, versiones ausentes/malformadas, observaciones inválidas y confirmación faltante.
10. Rechazo de formulario antiguo después de reprogramar.
11. Revalidación de asignación del médico y permisos revocados después de abrir el formulario.
12. Revalidación de permisos dentro del servicio.
13. Rechazo del envío repetido e intento de modificar campos protegidos.
14. Rechazo CSRF sin alterar cita ni historial.
15. Citas inexistentes y escape de HTML en nombres del paciente.

Ejecutar desde `sistema/backend` con PHP 8.4 y la conexión local de pruebas configurada:

```powershell
php artisan test tests/Feature/Citas/AsistenciaTest.php --compact
```

**Integración 23/10/2026:** las 15 pruebas pasaron dentro de la suite completa (183 pruebas / 2435 comprobaciones). En navegador se registró una cita iniciada como Atendida y se verificó su historial; cinco pantallas se midieron en siete anchos sin desbordamientos. [Resultados y límites](../../pruebas/Lucas/RESULTADOS.md).
