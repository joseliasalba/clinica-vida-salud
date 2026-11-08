# CU-14 — Actualizar perfil, salud declarada y contacto de emergencia

**Ciclo:** 2. **Responsable de la entrega:** Lucas. **Fuente:** Sistema Web de Gestión de Citas Médicas, catálogo y planificación del ciclo 2 (páginas 43–47 del PDF).

| Campo | Especificación |
| --- | --- |
| Objetivo | Mantener actualizados el contacto personal, la salud declarada y el contacto principal de emergencia. |
| Actor principal | Paciente |
| Actores secundarios | No intervienen en el flujo principal. |
| Precondiciones | Sesión vigente, permiso `perfil.propio` y ficha activa vinculada a la cuenta. |
| Evento inicial | Abrir «Mi perfil». |
| Resultado de éxito | Se actualizan los datos de la ficha propia en una transacción; la identidad y el correo de acceso se conservan. |
| Garantía ante fallo | La petición rechazada no modifica los datos de la operación. |

## Flujo principal

| Paso | Acción del actor | Respuesta del sistema |
| --- | --- | --- |
| 1 | Abre Mi perfil | Resuelve la ficha activa desde la cuenta autenticada y carga los datos existentes. |
| 2 | Edita teléfono, dirección, salud o contacto principal | Presenta los datos de contacto, salud declarada y emergencia en un formulario. |
| 3 | Guarda el perfil | Valida los campos y vuelve a resolver y bloquear la ficha vinculada. |
| 4 | Revisa la confirmación | Guarda el teléfono, dirección, perfil de salud y contacto principal; muestra confirmación. |

## Flujos alternativos y excepciones

| Código | Paso | Situación | Respuesta |
| --- | --- | --- | --- |
| A1 | 3 | Se declaran alergias sin detalle o el contacto está incompleto | Solicita completar los campos; no guarda. |
| A2 | 3 | Los cuatro campos de emergencia quedan vacíos | Elimina el contacto principal de la ficha. |
| E1 | 1 | Falta sesión, permiso o ficha activa vinculada | Redirige al acceso, deniega o devuelve ficha no encontrada, según corresponda. |

## Reglas de negocio y permisos

- El servidor toma el paciente de la sesión; no admite elegir otra ficha.
- La información de salud es declarada por el paciente. No modifica la historia clínica.
- Los antecedentes personales permiten registrar enfermedades crónicas como texto; no se edita el catálogo de enfermedades.
- El nombre y el código se muestran como referencia. Los datos editables de contacto son teléfono y dirección.

## Datos utilizados

Teléfono, dirección, tipo de sangre, alergias, medicamentos, antecedentes personales y familiares, y nombres, apellidos, parentesco y teléfono del contacto principal.

## Criterio de aceptación

- Dada una cuenta de paciente vinculada, la edición modifica su ficha aunque la petición incluya un identificador de otro paciente.

## Evidencia y pendientes

Ver [trazabilidad](dependencias.md), [pruebas](pruebas.md) y [diagramas editables](../../diagramas/CU-14.drawio).

Revisar en navegador el formulario, los campos incompletos y la eliminación del contacto principal.
