# Organización del proyecto

El proyecto mantiene las convenciones de Laravel y agrupa los archivos por funcionalidad dentro de cada capa. No se crean carpetas vacías para módulos que todavía no se implementaron.

## Mapa de carpetas

```text
docs/
├── casos-de-uso/
│   ├── README.md                  Catálogo, ciclos y enlaces
│   ├── PLANTILLA.md               Plantilla de especificación
│   ├── CU-01/
│   │   ├── especificacion.md      Objetivo, flujos y aceptación
│   │   ├── dependencias.md        Relación entre requisito, código y prueba
│   │   └── pruebas.md             Evidencias y verificaciones pendientes
│   └── CU-01.1/                   Misma organización para recuperación
├── modelo/                        Modelo de clases y notas
└── entorno.md                     Instalación y ejecución

sistema/backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/Auth/      Controladores de acceso y recuperación
│   │   ├── Requests/Auth/         Validación de sus formularios
│   │   └── Middleware/           Protección de sesiones
│   ├── Models/                    Entidades compartidas del dominio
│   ├── Jobs/                      Envío de enlaces en segundo plano
│   ├── Notifications/             Mensajes de correo
│   ├── Rules/                     Reglas de validación reutilizables
│   ├── Providers/                 Registro de servicios y permisos
│   └── Console/Commands/          Comandos de administración local
├── routes/
│   ├── web.php                    Entrada de rutas web e inicio
│   ├── auth.php                   Rutas de autenticación y recuperación
│   └── console.php                Rutas de consola
├── config/                        Configuración, sin credenciales
├── database/
│   ├── migrations/                Cambios de esquema ordenados
│   ├── seeders/                   Roles y permisos iniciales
│   └── factories/                 Datos de prueba
└── tests/
    ├── Feature/Auth/              Pruebas de acceso, permisos y recuperación
    ├── Feature/Citas/             Restricciones PostgreSQL de citas
    ├── Fixtures/Auth/             Proceso auxiliar de prueba concurrente
    └── Unit/                      Pruebas unitarias

sistema/frontend/
├── views/
│   ├── auth/                      Pantallas de acceso y recuperación
│   ├── components/                Componentes Blade compartidos
│   ├── layouts/                   Estructuras comunes
│   ├── errors/                    Páginas de error
│   └── inicio.blade.php           Inicio según permisos
├── css/
│   ├── app.css                    Entrada de estilos y configuración Tailwind
│   ├── shared/                    Base, elementos reutilizables y adaptación móvil
│   ├── auth/                      Estilos de autenticación
│   └── inicio/                    Estilos del inicio
└── js/
    ├── app.js                     Entrada de JavaScript
    └── auth/passwords.js          Mostrar contraseña y ayuda de longitud
```

Los puntos de entrada de Vite siguen siendo `css/app.css` y `js/app.js`. Laravel sirve el resultado compilado desde `backend/public/build/`.

## Cómo agregar una funcionalidad

1. Crear su carpeta `docs/casos-de-uso/CU-XX/` con especificación, dependencias y pruebas. Registrar el módulo, el ciclo y el estado en el catálogo.
2. Agrupar los controladores y validaciones en carpetas de función, por ejemplo `Controllers/Pacientes/` y `Requests/Pacientes/`. Usar el mismo nombre de función en pruebas y vistas.
3. Mantener los modelos en `app/Models/`: una entidad puede participar en varios casos de uso y no debe duplicarse por CU.
4. Agregar servicios solo cuando exista lógica de negocio que deba coordinarse o reutilizarse. No crear una capa DAO o repositorios que solo repitan Eloquent.
5. Crear las vistas en `frontend/views/pacientes/` y extraer elementos reutilizables a `views/components/`. El componente de mensajes se utiliza con `<x-messages />`.
6. Añadir estilos y JavaScript de esa función, importados desde sus entradas `app.css` y `app.js`. No copiar layouts ni mensajes en cada módulo.
7. Colocar pruebas HTTP en `tests/Feature/Pacientes/`, con namespace `Tests\Feature\Pacientes`. PHPUnit descubre recursivamente los archivos.
8. Registrar rutas en `routes/web.php` o cargar un archivo funcional desde allí cuando el módulo lo justifique. Todas conservan el middleware web y los controles de autenticación y permisos necesarios.

Módulos previstos: `Auth` (CU-01, CU-01.1 y CU-02), `Pacientes` (CU-03, CU-04 y CU-14), `Medicos` (CU-05 y CU-06), `Citas` (CU-07 a CU-12), `Reportes` (CU-13), `AtencionClinica` (CU-15 y CU-16) y `Recordatorios` (CU-17). Es una organización propuesta; las carpetas aparecen al implementar cada función.

## Documentación y estados

- **DEFINIDO:** requisito identificado en el enunciado o decisión acordada con el usuario. Registrar cuál es su fuente; no confundir ambas.
- **PROPUESTO:** decisión de diseño que todavía requiere discusión.
- **PENDIENTE:** no implementado, no comprobado o sujeto a una aclaración. Indicar exactamente qué falta.

La especificación describe el comportamiento; `dependencias.md` conecta requisitos, tablas, archivos y pruebas; `pruebas.md` registra resultados observados y límites. Una prueba automatizada no acredita por sí sola diseño visual, entrega real de correo ni aprobación docente.

La separación backend/frontend organiza responsabilidades. Las vistas Blade siguen siendo renderizadas por Laravel; no son una aplicación independiente que consuma una API.

## Estándar de adaptación a móviles

Las instrucciones para los próximos cambios están en [`sistema/frontend/AGENTS.md`](../sistema/frontend/AGENTS.md). Los estilos compartidos de administración se encuentran en `frontend/css/shared/admin.css`; ya no pertenecen exclusivamente a Auth. La adaptación general se centraliza en `shared/responsive.css` y la de listados en `shared/tables.css`.

Cada listado utiliza el mismo componente, sin repetir sus datos para móvil y escritorio:

```blade
<x-responsive-table label="Lista de pacientes"
    :columns="['nombre' => 'Paciente', 'telefono' => 'Teléfono', 'acciones' => 'Acciones']">
    @forelse($pacientes as $paciente)
        <tr role="row">
            <x-table-cell column="nombre" heading>{{ $paciente->nombres }}</x-table-cell>
            <x-table-cell column="telefono">{{ $paciente->telefono }}</x-table-cell>
            <x-table-cell column="acciones">
                <div class="record-actions"><a class="table-action" href="{{ route('pacientes.show', $paciente) }}">Ver ficha</a></div>
            </x-table-cell>
        </tr>
    @empty
        <x-table-empty>No se encontraron pacientes.</x-table-empty>
    @endforelse
</x-responsive-table>
```

Las etiquetas de cada celda y los encabezados se generan desde `columns`; una clave inexistente provoca un error explícito en vez de perder una etiqueta móvil. La tabla conserva encabezados, roles y relaciones de fila para lectores de pantalla. En tarjetas las etiquetas visuales no se anuncian por duplicado.

| Ancho disponible del listado | Presentación |
| --- | --- |
| Menos de 26rem | Tarjetas con etiquetas sobre cada valor; teléfono, estado y acciones siempre presentes |
| Desde 26rem hasta menos de 44rem | Tarjetas con etiqueta y valor en dos columnas flexibles |
| Desde 44rem | Tabla completa, con anchos flexibles y ajuste de texto |

La adaptación depende del contenedor, no de reconocer un teléfono concreto. Si un navegador no admite consultas de contenedor, conserva la presentación de tarjetas. El contenido no se esconde para eliminar el desbordamiento.

Los contenedores generales usan `--page-gutter` y `--content-max`; paneles, `--panel-padding`. Las reglas generales cambian a 48rem (formularios, navegación, botones, contacto y autenticación) y 62.5rem (distribución intermedia). No agregar nuevos márgenes rígidos, `min-width` de escritorio ni recortes del contenido para un módulo.

`backend/tests/Feature/ResponsiveLayoutTest.php` verifica todos los listados actuales, valores largos, etiquetas coherentes, acciones presentes y estados vacíos; también impide añadir tablas HTML fuera del componente común. Es una garantía estructural, no una medición del desbordamiento en un navegador.

Antes de aceptar cambios de interfaz, revisar 320, 360, 390, 430, 768, 1024 y 1440px y zoom al 200%, con nombres, correos y códigos extensos. Comprobar login/recuperación, inicio, los cuatro listados, formularios, ficha, filtros, paginación y teclado. Si no hay navegador disponible, registrar esa verificación como pendiente.

## Ejecución de pruebas

Desde la raíz del repositorio, en PowerShell:

```powershell
. .\scripts\activar-entorno.ps1
Set-Location sistema/backend
php artisan test
php artisan test tests/Feature/Auth
php artisan test tests/Feature/Citas
```

Las pruebas usan PostgreSQL en `clinica_testing`. Las migraciones conservan su orden en `database/migrations/`; no se distribuyen entre carpetas de casos de uso. `vendor/`, `node_modules/`, `.tools/` y recursos compilados son dependencias o archivos generados.
