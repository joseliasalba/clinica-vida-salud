@extends('layouts.app')
@section('title', 'Bienvenido')
@section('header-class', 'site-header-public site-header-sticky')
@section('header-actions')
    <nav class="public-navigation" aria-label="Información de la clínica">
        <a href="#clinica">La clínica</a><a href="#servicios">Servicios</a><a href="#contacto">Contacto</a>
        @auth
            <a class="button primary" href="{{ route('inicio') }}">Mi cuenta <span aria-hidden="true">↗</span></a>
        @else
            <a class="button secondary" href="{{ route('login') }}">Iniciar sesión <span aria-hidden="true">↗</span></a>
        @endauth
    </nav>
@endsection
@section('content')
<div class="public-shell">
    <section class="public-hero" aria-labelledby="welcome-title">
        <div class="public-hero-copy">
            <span class="eyebrow">CLÍNICA VIDA SALUD · CERCA DE TI</span>
            <h1 id="welcome-title">Tu bienestar,<br><em>nuestro punto<br>de encuentro.</em></h1>
            <p>Conoce nuestra clínica y encuentra un espacio para conectar con tu atención. Tu próximo paso empieza aquí.</p>
            <div class="public-actions"><a class="button primary" href="#citas">Sacar ticket / reservar cita <span aria-hidden="true">↗</span></a>
                @guest<a class="button secondary" href="{{ route('registro') }}">Soy nuevo: registrarme</a>@else<a class="button secondary" href="{{ route('inicio') }}">Ir a mi cuenta</a>@endguest
            </div>
            <span class="public-caption">Una cuenta personal. Un lugar para acompañarte.</span>
        </div>
        <aside class="public-welcome-card" aria-label="Cómo comenzar">
            <div class="public-cross" aria-hidden="true">✚</div>
            <span class="eyebrow">TU ATENCIÓN, PASO A PASO</span><h2>Estamos para<br>acompañarte.</h2>
            <ol><li><strong>Crea tu cuenta</strong><span>Registra tus datos y confirma tu correo.</span></li><li><strong>Accede a tu espacio</strong><span>Ingresa con tu usuario o correo y contraseña.</span></li><li><strong>Prepara tu próxima visita</strong><span>La reserva de citas estará disponible próximamente.</span></li></ol>
        </aside>
    </section>
    <section id="clinica" class="public-introduction" aria-labelledby="clinic-title"><span class="eyebrow">CONOCE VIDA SALUD</span><h2 id="clinic-title">Atención que empieza contigo.</h2><p>Queremos que dar el primer paso sea sencillo: encontrar información clara, preparar tus datos y tener un acceso personal a los servicios de la clínica.</p></section>
    <section id="servicios" class="public-section" aria-labelledby="services-title">
        <div class="public-section-heading"><div><span class="eyebrow">NUESTROS SERVICIOS</span><h2 id="services-title">Un espacio para cada cuidado.</h2></div>@if(config('clinica.datos_demostracion'))<span class="badge">Especialidades de ejemplo</span>@endif</div>
        <div class="public-services">@foreach(config('clinica.especialidades') as $especialidad)<article class="public-service"><span class="module-icon"><x-navigation-icon name="salud" /></span><h3>{{ $especialidad['nombre'] }}</h3><p>{{ $especialidad['descripcion'] }}</p></article>@endforeach</div>
    </section>
    <section id="citas" class="public-ticket" aria-labelledby="ticket-title">
        <div><span class="eyebrow">TU PRÓXIMA VISITA</span><h2 id="ticket-title">Empieza por tu cuenta de paciente.</h2><p>Regístrate si es tu primera vez o inicia sesión si ya tienes cuenta. Si recepción ya registró tu ficha, solicita ayuda para vincularla.</p><p class="public-ticket-status"><strong>Reserva en línea próximamente.</strong> Por ahora no se emiten tickets desde este portal. Crear una cuenta no confirma una cita ni abre una historia clínica.</p></div>
        <div class="public-actions">@guest<a class="button primary" href="{{ route('registro') }}">Crear mi cuenta <span aria-hidden="true">↗</span></a><a class="button secondary" href="{{ route('login') }}">Ya tengo cuenta</a>@else<a class="button primary" href="{{ route('inicio') }}">Entrar a mi cuenta <span aria-hidden="true">↗</span></a>@endguest</div>
    </section>
    <section id="contacto" class="public-section" aria-labelledby="contact-title">
        <span class="eyebrow">ENCUÉNTRANOS</span><h2 id="contact-title">Hablemos de tu próxima visita.</h2>
        @if(config('clinica.datos_demostracion'))<p class="public-demo-note">Sitio de demostración: la dirección, el teléfono, los horarios y las especialidades son datos de ejemplo pendientes de confirmación.</p>@endif
        <dl class="public-contact"><div><dt>Dirección</dt><dd>{{ config('clinica.direccion') }}</dd></div><div><dt>Teléfono</dt><dd>{{ config('clinica.telefono') }}</dd></div><div><dt>Horario de atención</dt><dd>{{ config('clinica.horarios') }}</dd></div></dl>
    </section>
</div>
@endsection
