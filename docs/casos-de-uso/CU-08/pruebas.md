# Verificación de CU-08

Pruebas automatizadas: `sistema/backend/tests/Feature/Citas/ReprogramacionTest.php`.

**Integración 23/10/2026:** 15 pruebas de reprogramación incluidas en la suite completa aprobada (183 pruebas / 2435 comprobaciones). Se verificó CSRF además de los escenarios iniciales y se recorrió una reprogramación real con datos ficticios en navegador. [Resultados](../../pruebas/Lucas/RESULTADOS.md).

| Grupo | Comportamiento comprobado |
|---|---|
| Flujo completo | Recepción reprograma cita confirmada y conserva paciente, especialidad, estado y auditoría anterior/nueva |
| Formulario | Consulta y selección de radio funcionan sin JavaScript |
| Disponibilidad | Rechaza cruces de paciente con otros médicos y turnos ocupados, pasados, fuera de horario, desalineados y bloqueados |
| Horarios | Comprueba día ISO, vigencia, actividad y duración positiva |
| Estados | Rechaza reprogramar canceladas, completadas, en curso e inasistencias |
| Catálogos | Rechaza paciente, médico, especialidad o vínculo inactivos |
| Cambio de médico | Permite otro médico de la misma especialidad; rechaza uno ajeno |
| Seguridad | Exige permiso y sesión; valida JSON y no admite alterar paciente, especialidad, estado ni fin |
| Concurrencia | Rechaza versión antigua y revierte conflicto PostgreSQL `23P01`, sin perder la reserva original ni crear auditoría |
| Ocupación | Incluye turnos cancelados y excluye todos los demás estados, incluidas inasistencias |

Ejecutar con la configuración de PostgreSQL de pruebas, nunca contra datos de trabajo:

```powershell
php artisan test tests/Feature/Citas/ReprogramacionTest.php --compact
```

La revisión visual en navegador se valida durante la integración del conjunto de CU de Lucas. Los tests HTTP comprueban estructura y contenido; no verifican geometría de pantalla.

**Integración 24/10/2026:** CU-08 cuenta con 16 pruebas tras añadir el cruce de paciente entre médicos. La revisión independiente ejecutó estas y tres de integración: 19 aprobadas / 226 comprobaciones.
