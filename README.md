# Clínica Vida Salud

Proyecto universitario de Ingeniería de Requisitos / SI1: sistema web de gestión de citas médicas para una clínica ficticia.

## Estado actual

- Repositorio preparado para el trabajo de dos integrantes.
- Modelo de clases propuesto: 24 clases, 32 asociaciones y un único diagrama de StarUML.
- Catálogo inicial y plantilla para especificar casos de uso.
- Aplicación Laravel 13 creada en `sistema/backend/`, conectada localmente a PostgreSQL 17.
- Las 29 migraciones están ejecutadas, incluyendo el dominio clínico y la protección contra citas superpuestas.
- Autenticación preparada sobre `usuarios`, cuatro roles y ocho permisos iniciales; horario `America/La_Paz`.
- 57 pruebas aprobadas con 451 comprobaciones y PostgreSQL en el esquema aislado `clinica_testing`.
- Acceso, cierre de sesión, recuperación y gestión de usuarios, roles y permisos implementados; revisión en navegador y entrega SMTP real pendientes. Los flujos clínicos posteriores siguen pendientes.

## Tecnologías acordadas

- PHP con Laravel.
- PostgreSQL.
- HTML y CSS; Blade para las pantallas.
- JavaScript para mejorar los formularios cuando sea necesario.

Entorno comprobado: PHP 8.4, Composer 2, Laravel 13, PostgreSQL 17 y Node.js 24. Se incluyen `composer.lock` y `package-lock.json`; las credenciales locales se mantienen fuera de Git.

## Organización

| Ruta | Contenido |
| --- | --- |
| `docs/modelo/` | Diagrama editable y notas del diseño |
| `docs/casos-de-uso/` | Catálogo y especificaciones de casos de uso |
| `docs/entorno.md` | Estado de herramientas y preparación local |
| `docs/estructura.md` | Módulos y ubicación de controladores, vistas, recursos y pruebas |
| `CONTRIBUTING.md` | Flujo de trabajo para el equipo |
| `.github/` | Plantillas de tareas y pull requests |
| `scripts/` | Preparación y activación del entorno Windows |
| `sistema/backend/` | Laravel: lógica, rutas, migraciones, pruebas y servidor |
| `sistema/frontend/` | Vistas Blade, CSS, JavaScript y compilación con Vite |

## Alcance

Cuatro perfiles: administrador, recepción, médico y paciente. El sistema contempla registro de pacientes, médicos y especialidades, horarios, reservas, reprogramaciones, cancelaciones, asistencia, agenda, historial y reportes. El paciente se registra por internet y recepción puede asistirlo.

El segundo ciclo incluye perfil de salud, consultas, tratamientos, estudios, resultados y recordatorios. Su profundidad y algunos permisos requieren aclaración.

## Reglas prioritarias

1. CI único cuando se informa; no duplicar el paciente al vincular una cuenta.
2. Un médico no puede tener citas superpuestas, incluso entre especialidades o ante reservas simultáneas.
3. Reprogramación atómica: si falla, se conserva la reserva original.
4. Una cancelación libera disponibilidad y conserva el historial.
5. Desactivar pacientes y médicos con historial en lugar de eliminarlos.
6. Comprobar permisos en el servidor y distinguir datos declarados de anotaciones profesionales.

## Primeros pasos

1. Leer [la guía de entorno](docs/entorno.md).
2. Revisar [el modelo](docs/modelo/README.md).
3. Especificar los casos de uso del [catálogo inicial](docs/casos-de-uso/README.md).
4. Trabajar en ramas cortas y revisar los cambios según [CONTRIBUTING.md](CONTRIBUTING.md).

Consultar [la guía de estructura](docs/estructura.md) antes de agregar archivos y [el índice de casos de uso](docs/casos-de-uso/README.md) para encontrar especificaciones, dependencias y evidencias.

## Entregas según el enunciado

- Primer avance: 24/10/2026, 23:59, al menos 50 % con documentación e implementación.
- Segunda entrega: el escrito indica «domingo 07/11/2026»; existe una contradicción entre día y fecha. Confirmar con la docente. Planificar la entrega antes del 07/11 a las 23:59 mientras se aclara.
- Defensa: 09/11/2026, 07:00. Horario de Bolivia.

Ambas entregas son obligatorias. El repositorio, las ramas y la documentación no acreditan por sí solos un porcentaje de implementación.
