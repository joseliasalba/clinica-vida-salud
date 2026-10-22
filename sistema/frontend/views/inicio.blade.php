@extends('layouts.app')
@section('title', 'Mi inicio')
@section('content')
<div class="dashboard">
    <div class="dashboard-top"><div><span class="eyebrow">TU ESPACIO PERSONAL</span><h1>Hola, {{ auth()->user()->nombre_usuario }}.</h1><p>Bienvenido a Clínica Vida Salud.</p></div><form action="{{ route('logout') }}" method="POST">@csrf<button class="button secondary" type="submit">Cerrar sesión ↗</button></form></div>
    <x-messages />
    <section class="welcome-banner"><div><span class="eyebrow">ESTAMOS CONTIGO</span><h2>Todo empieza con<br>una buena atención.</h2><p>Este es tu punto de encuentro con la clínica.</p></div><span class="banner-cross" aria-hidden="true">✚</span></section>
    <div class="section-heading"><h2>Tu espacio de atención</h2><span>Opciones de tu cuenta</span></div>
    <div class="module-grid">
        @php($available = false)
        @foreach (config('permisos.catalogo') as $codigo => $descripcion)
            @can($codigo)
                @php($available = true)
                <article class="module-card"><span class="module-icon" aria-hidden="true">✚</span><h3>{{ $descripcion }}</h3>@if($codigo === 'usuarios.gestionar')<p>Organiza las cuentas y las funciones de cada persona.</p><a class="button primary" href="{{ route('usuarios.index') }}">Administrar acceso ↗</a>@elseif($codigo === 'pacientes.gestionar')<p>Registra pacientes y mantén sus fichas y datos de contacto.</p><a class="button primary" href="{{ route('pacientes.index') }}">Gestionar pacientes ↗</a>@elseif($codigo === 'medicos.gestionar')<p>Organiza el catálogo de especialidades médicas de la clínica.</p><a class="button primary" href="{{ route('especialidades.index') }}">Gestionar especialidades ↗</a>@else<p>Este servicio estará disponible próximamente.</p><span class="badge">Próximamente</span>@endif</article>
            @endcan
        @endforeach
    </div>
    @if (! $available)<div class="notice">Tu cuenta todavía no tiene funciones habilitadas. Contacta a la administración de la clínica.</div>@endif
</div>
@endsection
