<nav class="sidebar-navigation" aria-label="Navegación principal">
    <a class="sidebar-link" href="{{ route('inicio') }}" @if(request()->routeIs('inicio')) aria-current="page" @endif>
        <x-navigation-icon name="inicio" /><span>Inicio</span>
    </a>
    @foreach(config('navegacion.grupos') as $grupo)
        @php
            $opciones = collect($grupo['opciones'])->filter(fn (array $opcion): bool => auth()->user()->can($opcion['permiso']));
        @endphp
        @if($opciones->isNotEmpty())
            <details class="sidebar-group" open>
                <summary>
                    <x-navigation-icon :name="$grupo['icono']" /><span>{{ $grupo['titulo'] }}</span>
                    <x-navigation-icon name="flecha" class="sidebar-chevron" />
                </summary>
                <div class="sidebar-group-links">
                    @foreach($opciones as $opcion)
                        @if($opcion['ruta'])
                            <a class="sidebar-link" href="{{ route($opcion['ruta']) }}" @if(request()->routeIs(...(array) $opcion['activo'])) aria-current="page" @endif>
                                <x-navigation-icon :name="$opcion['icono']" /><span>{{ $opcion['titulo'] }}</span>
                            </a>
                        @else
                            <div class="sidebar-link sidebar-pending" aria-disabled="true">
                                <x-navigation-icon :name="$opcion['icono']" /><span>{{ $opcion['titulo'] }}<small>Próximamente</small></span>
                            </div>
                        @endif
                    @endforeach
                </div>
            </details>
        @endif
    @endforeach
</nav>
