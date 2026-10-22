<dialog id="site-navigation" class="sidebar" aria-labelledby="navigation-title">
    <div class="sidebar-header">
        <div><span class="sidebar-clinic">CLÍNICA VIDA SALUD</span><h2 id="navigation-title">Navegación</h2></div>
        <form method="dialog"><button class="sidebar-close" aria-label="Cerrar menú" autofocus><x-navigation-icon name="cerrar" /></button></form>
    </div>
    <x-navigation-links />
    <div class="sidebar-account">
        <span class="sidebar-account-label">Sesión iniciada como</span>
        <strong>{{ auth()->user()->nombre_usuario }}</strong>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="sidebar-link sidebar-logout" type="submit"><x-navigation-icon name="salir" /><span>Cerrar sesión</span></button>
        </form>
    </div>
</dialog>
<noscript><div class="navigation-fallback"><x-navigation-links /></div></noscript>
