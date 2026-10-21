# Trabajo del equipo

## Ramas

- `main`: versión integrada que ambos pueden usar como base.
- `docs/cu-XX-nombre`: especificaciones y diagramas de un caso de uso.
- `feat/cu-XX-nombre`: implementación del caso de uso.
- `fix/descripcion`: correcciones.

Para dos integrantes se propone trabajar con ramas cortas y pull requests a `main`. No se necesita una rama `develop` permanente.

## Flujo habitual

Antes de comenzar, comprobar que no hay cambios locales pendientes:

```bash
git status
git switch main
git pull --ff-only origin main
git switch -c docs/cu-01-iniciar-sesion
```

Editar los archivos del caso de uso y revisar lo que se enviará:

```bash
git diff
git add docs/casos-de-uso/
git diff --cached
git commit -m "docs: especificar CU-01 iniciar sesión"
git push -u origin docs/cu-01-iniciar-sesion
```

Abrir un pull request a `main`. El otro integrante revisa los cambios antes de integrarlos. Este flujo es un acuerdo del equipo; la protección de la rama no está configurada todavía.

## Archivos compartidos

- Asignar un único responsable de editar el `.mdj` a la vez. Una edición simultánea puede producir conflictos difíciles de resolver aunque sea JSON.
- Versionar `composer.lock` y el lockfile de npm cuando se cree la aplicación.
- Cada integrante mantiene su propio `.env`; publicar solo `.env.example` sin credenciales.
- Usar datos ficticios para pruebas y demostraciones.
- No subir `vendor`, `node_modules`, respaldos de la base ni archivos privados de pacientes.

## Validación

Cada pull request indica qué se comprobó y qué sigue pendiente. Los comandos de pruebas PHP se añadirán cuando exista la aplicación Laravel; por ahora no hay pruebas de software ejecutables.
