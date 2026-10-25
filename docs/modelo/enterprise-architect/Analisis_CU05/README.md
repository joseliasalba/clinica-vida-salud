# Analisis de clases de CU-05
Abrir [el modelo editable en Enterprise Architect](Clinica_Vida_Salud_Analisis_CU05.eapx), expandir la raiz del proyecto y abrir **CU-05 Diagrama de clases de analisis**. Se entrega [imagen PNG](diagramas/cu05-clases-analisis.png) y [XMI 2.1](Clinica_Vida_Salud_Analisis_CU05.xmi).
## Alcance
CU-05 mantiene medicos, especialidades, sus asignaciones y la vinculacion opcional con una cuenta existente. Requiere sesion y permiso efectivo `medicos.gestionar`. Fecha de contraste con el codigo: 24/10/2026.
El diagrama contiene actor y seis clases conceptuales de analisis:
| Tipo | Clase | Responsabilidad |
| --- | --- | --- |
| boundary | V.Médicos y especialidades | Listados, filtros, formularios, seleccion de cuenta y confirmacion de estado. |
| control | C.Gestión de médicos y especialidades | Coordinacion, autorizacion, validacion, guardado transaccional, asignaciones, cuenta elegible, version y estado. |
| entity | E.Médico | Identidad profesional, datos administrativos y estado del medico. |
| entity | E.Especialidad | Identidad, nombre normalizado, descripcion y estado de la especialidad. |
| entity | E.Asignación de especialidad | Relacion entre un medico y una especialidad; conserva el estado del vinculo sin borrarlo. |
| entity | E.Cuenta de usuario | Identidad de la cuenta existente que puede vincularse al medico. |
El control se realiza mediante `MedicoController`, `EspecialidadController`, los Form Requests, `DoctorManagement` y `SpecialtyManagement`. Es una responsabilidad de analisis agregada, no una clase PHP nueva. Las operaciones de las entidades expresan responsabilidades conceptuales y pueden realizarse en modelos o servicios.
La asignacion representa `medico_especialidad`, operado con Query Builder. No existe un modelo Eloquent adicional de ese nombre. Cada asignacion pertenece a un medico y una especialidad; ambos pueden conservar cero o muchas asignaciones historicas. Al crear o reactivar un medico se comprueba la disponibilidad de especialidades activas.
El vinculo Medico-Cuenta es opcional y unico en ambos extremos. La cuenta elegida debe cumplir las condiciones de elegibilidad del servicio cuando se cambia la vinculacion. CU-05 no crea usuarios ni asigna roles. La firma de version se calcula con HMAC; no es una columna agregada. La desactivacion conserva historial, citas, horarios, cuentas y asociaciones.
Se sigue la presentacion de CU-01/CU-02 y del grupo de clases de analisis existente: actor, clases rectangulares crema, estereotipos, atributos, operaciones y asociaciones. No hay notas explicativas visibles ni se añaden clases de otros casos de uso.
## Fuentes
Rutas de medicos y especialidades; controladores y Form Requests de ambos modulos; `DoctorManagement`, `SpecialtyManagement`, `Medico`, `Especialidad` y `User`. Las rutas completas se conservan en `modelo.json` y en las propiedades de EA.
## Regeneracion
Con EA 15 iniciado sin otro proyecto, ejecutar `generar-ea.ps1` mediante Windows PowerShell 5.1. Se requiere la API COM y la plantilla instalada `EABase.eapx`. `-Recrear` conserva un respaldo de este archivo generado, pero no fusiona ediciones manuales. Los proyectos existentes no se sustituyen. `modelo.json` es la fuente, y los manifiestos conservan los identificadores de EA. No se genera PDF.
