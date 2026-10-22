@extends('layouts.app')
@section('content')
<div class="auth-shell">
    <aside class="auth-story" aria-label="Bienvenida">
        <span class="eyebrow">CERCA DE TI</span>
        <h1>Tu bienestar.<br>Nuestro<br><em>compromiso.</em></h1>
        <p>Un espacio para conectar con tu atención médica, con confianza y tranquilidad.</p>
        <div class="care-art" aria-hidden="true"><div class="care-ring"></div><span>✚</span><i></i></div>
        <div class="story-foot"><span class="status-dot"></span> Tu atención, en un mismo lugar</div>
    </aside>
    <section class="auth-card" aria-labelledby="form-title">
        <span class="eyebrow">PORTAL VIDA SALUD</span>
        @yield('form')
        <p class="help-note">¿Necesitas ayuda con tu cuenta?<br>Contacta a la recepción de la clínica.</p>
    </section>
</div>
@endsection
