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
    <header class="site-header">
        <a class="brand" href="{{ route('inicio') }}" aria-label="Clínica Vida Salud, inicio"><span class="brand-symbol" aria-hidden="true">✚</span><span>vida<span class="brand-light">salud</span><small>CLÍNICA MÉDICA</small></span></a>
        <span class="header-note">Cuidamos de ti, en cada paso.</span>
    </header>
    <main id="contenido" tabindex="-1">@yield('content')</main>
    <footer class="site-footer"><span>Clínica Vida Salud</span><span>Atención que empieza contigo.</span></footer>
</body>
</html>
