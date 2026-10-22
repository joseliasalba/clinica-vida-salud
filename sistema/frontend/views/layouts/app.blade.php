<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="no-referrer">
    <title>@yield('title', 'Mi cuenta') · Clínica Vida Salud</title>
    @vite(['css/app.css', 'js/app.js'])
</head>
<body>
    <a class="skip-link" href="#contenido">Ir al contenido</a>
    <header class="site-header{{ auth()->check() ? ' site-header-sticky' : '' }} @yield('header-class')">
        <div class="header-navigation">
            @auth
                <button type="button" class="navigation-toggle" data-navigation-open aria-label="Abrir menú de navegación" aria-controls="site-navigation" aria-haspopup="dialog" aria-expanded="false" hidden><x-navigation-icon name="menu" /><span class="navigation-toggle-label">Menú</span></button>
            @endauth
            <a class="brand" href="{{ route('portada') }}" aria-label="Clínica Vida Salud, página principal"><span class="brand-symbol" aria-hidden="true">✚</span><span>vida<span class="brand-light">salud</span><small>CLÍNICA MÉDICA</small></span></a>
        </div>
        @hasSection('header-actions')
            @yield('header-actions')
        @else
            <span class="header-note">Cuidamos de ti, en cada paso.</span>
        @endif
    </header>
    @auth
        <x-sidebar />
    @endauth
    <main id="contenido" tabindex="-1">@yield('content')</main>
    <footer class="site-footer"><span>Clínica Vida Salud</span><span>Atención que empieza contigo.</span></footer>
</body>
</html>
