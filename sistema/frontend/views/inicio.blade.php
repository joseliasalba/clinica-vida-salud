@extends('layouts.app')
@section('title', 'Mi inicio')
@section('content')
<div class="dashboard">
    <div class="dashboard-top"><div><span class="eyebrow">TU ESPACIO PERSONAL</span><h1>Hola, {{ auth()->user()->nombre_usuario }}.</h1><p>Bienvenido a Clínica Vida Salud.</p></div><form action="{{ route('logout') }}" method="POST">@csrf<button class="button secondary" type="submit">Cerrar sesión ↗</button></form></div>
    <x-messages />
    @if(!auth()->user()->hasVerifiedEmail() && \App\Models\Paciente::query()->where('usuario_id', auth()->id())->exists())
        <div class="notice">Tu correo está pendiente de confirmación. <a href="{{ route('verification.notice') }}">Verificar mi correo</a>.</div>
    @endif
    <section class="welcome-banner"><div><span class="eyebrow">ESTAMOS CONTIGO</span><h2>Todo empieza con<br>una buena atención.</h2><p>Este es tu punto de encuentro con la clínica.</p></div><span class="banner-cross" aria-hidden="true">✚</span></section>
    <div class="section-heading"><h2>Tu espacio de atención</h2><span>Opciones de tu cuenta</span></div>
    <div class="module-grid">
        @php($available = false)
        @foreach (config('permisos.catalogo') as $codigo => $descripcion)
            @can($codigo)
                @php($available = true)
                <article class="module-card">
                    <span class="module-icon" aria-hidden="true">✚</span><h3>{{ $descripcion }}</h3>
                    @if($codigo === 'usuarios.gestionar')
                        <p>Organiza las cuentas y las funciones de cada persona.</p><a class="button primary" href="{{ route('usuarios.index') }}">Administrar acceso ↗</a>
                    @elseif($codigo === 'pacientes.gestionar')
                        <p>Registra pacientes y mantén sus fichas y datos de contacto.</p><a class="button primary" href="{{ route('pacientes.index') }}">Gestionar pacientes ↗</a>
                    @elseif($codigo === 'medicos.gestionar')
                        <p>Organiza las fichas profesionales y las especialidades de la clínica.</p><div class="record-actions"><a class="button primary" href="{{ route('medicos.index') }}">Gestionar médicos ↗</a><a class="button secondary" href="{{ route('especialidades.index') }}">Especialidades</a></div>
                    @elseif($codigo === 'horarios.gestionar')
                        <p>Configura las franjas semanales y los bloqueos por ausencias.</p><a class="button primary" href="{{ route('horarios.index') }}">Configurar horarios ↗</a>
                    @elseif($codigo === 'citas.gestionar')
                        <p>Reprograma, cancela y registra el estado de las citas existentes.</p><a class="button primary" href="{{ route('citas.gestion') }}">Gestionar citas ↗</a><a class="button secondary" href="{{ route('citas.index') }}">Reservar cita</a>
                    @elseif($codigo === 'agenda.propia')
                        <p>Registra la atención o inasistencia de tus citas.</p><a class="button primary" href="{{ route('citas.gestion') }}">Registrar asistencia ↗</a>
                    @elseif($codigo === 'citas.propias')
                        <p>Consulta las fechas y los estados de tus citas registradas.</p><a class="button primary" href="{{ route('historial.index') }}">Ver mi historial ↗</a><a class="button secondary" href="{{ route('citas.index') }}">Reservar cita</a>
                    @else
                        <p>Este servicio estará disponible próximamente.</p><span class="badge">Próximamente</span>
                    @endif
                </article>
            @endcan
        @endforeach
    </div>
    @if (! $available)<div class="notice">Tu cuenta todavía no tiene funciones habilitadas. Contacta a la administración de la clínica.</div>@endif
</div>
@endsection
