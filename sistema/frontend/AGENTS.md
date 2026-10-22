# Estándar de interfaz adaptable

- Mantener la identidad visual existente. Laravel renderiza las vistas de este directorio; no es una SPA independiente.
- Las reglas comunes están en `css/shared/base.css`, `admin.css`, `tables.css` y `responsive.css`, importadas desde `css/app.css`. Los estilos de un módulo no deben duplicar esas reglas ni contradecir sus puntos de adaptación.
- Todo listado tabular debe usar `<x-responsive-table :columns="..." label="...">`, `<x-table-cell column="...">` y `<x-table-empty>`. Declarar etiquetas una sola vez en `columns`; usar `heading` en la celda identificadora y `role="row"` en cada fila. No añadir tablas HTML directas en otras vistas: la prueba `ResponsiveLayoutTest` las detecta.
- Cada registro se presenta completo en tarjetas dentro de contenedores estrechos y como tabla al disponer de al menos `44rem`. No ocultar columnas, teléfonos, estados, formularios o acciones para hacer caber un listado. No duplicar datos en dos versiones HTML para móvil/escritorio.
- No usar `overflow-x: hidden` en la página para tapar desbordamientos. Reservar los recortes para elementos decorativos. No imponer un ancho mínimo fijo al contenido funcional.
- Usar `minmax(0, 1fr)` en columnas flexibles, permitir que los elementos de grid/flex encojan y que textos largos se partan. Los contenedores compartidos tienen márgenes fluidos mediante `--page-gutter`; no sumar otro juego de márgenes laterales a esos contenedores.
- Las acciones deben poder saltar de línea y tener al menos 44px de alto. Los campos usan 16px en pantallas de hasta `48rem`. Conservar el zoom del navegador y el foco visible.
- Verificar datos extensos y estados vacíos, además de anchos de 320, 360, 390, 430, 768, 1024 y 1440px y zoom al 200%. En móviles toda información y acción debe ser accesible sin desplazar horizontalmente la página.
- Desde `backend/`, ejecutar las pruebas pertinentes, incluida `php artisan test tests/Feature/ResponsiveLayoutTest.php --compact`. Desde este directorio, compilar con `npm run build`. No incorporar dependencias nuevas solo para ajustes de CSS.
- Las pruebas HTTP verifican contenido, etiquetas, accesibilidad estructural y navegación; no prueban geometría en píxeles. Usar un navegador para validar distribución y desbordamientos cuando esté disponible; si no lo está, declarar pendiente la revisión visual sin presentarla como aprobada.
