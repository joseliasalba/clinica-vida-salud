# Modelo de datos

## Diagramas de los casos implementados en Enterprise Architect

El proyecto editable [CU-01 y CU-02](enterprise-architect/Clinica_Vida_Salud_CU01_CU02.eapx) contiene 30 diagramas de requisitos, análisis, diseño e implementación, siguiendo el formato de `todos los diagramas.pdf`. Ver el [informe PDF](enterprise-architect/Diagramas_CU01_CU02.pdf) y la [guía del modelo](enterprise-architect/README.md).

## Modelo general de dominio en StarUML

Abrir `Clinica_Vida_Salud.mdj` con StarUML. El modelo tiene una única vista general, 24 clases y 32 asociaciones.

Es una propuesta de diseño basada en el enunciado, el contexto de continuidad y las entidades legibles de la referencia del usuario. Se comprobó la estructura del archivo y una vista previa de sus coordenadas; queda pendiente su revisión visual completa en StarUML.

## Decisiones por aclarar

- Representación de tutores y menores sin CI.
- Vinculación y recuperación de cuentas existentes.
- Campos personales editables por el paciente.
- Alcance del acceso clínico histórico del médico.
- Responsable de cargar y publicar resultados de estudios.
- Profundidad de antecedentes, tratamientos y solicitudes de estudios.
- Ubicación y modalidad de atención.
- Canal y anticipación de recordatorios.

## Reglas a implementar

- No superposición de citas del mismo médico, con protección ante reservas concurrentes.
- Médico/especialidad válidos y horario perteneciente al médico seleccionado.
- Conservar inicio y fin de las citas existentes cuando cambien configuraciones futuras.
- Reprogramación atómica y trazabilidad de cambios.
- Separar información declarada por el paciente de anotaciones profesionales.
- Diferenciar alergias desconocidas de ausencia confirmada de alergias.

El archivo UML no constituye una base PostgreSQL implementada ni demuestra todavía estas validaciones.
