@props(['name'])
@php
    $paths = [
        'menu' => 'M4 6h16M4 12h16M4 18h16',
        'cerrar' => 'm6 6 12 12M6 18 18 6',
        'inicio' => 'm3 10 9-7 9 7M5 9v12h5v-7h4v7h5V9',
        'personas' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M16 3a4 4 0 0 1 0 8M22 21v-2a4 4 0 0 0-3-3.87M13 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z',
        'persona' => 'M20 21v-2a7 7 0 0 0-14 0v2M17 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z',
        'salud' => 'M9 3h6v6h6v6h-6v6H9v-6H3V9h6Z',
        'calendario' => 'M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2ZM8 14h2M14 14h2M8 18h2',
        'reloj' => 'M12 8v4l3 2M22 12a10 10 0 1 1-20 0 10 10 0 0 1 20 0Z',
        'escudo' => 'M12 3 3 7v5c0 5 9 10 9 10s9-5 9-10V7ZM9 12l2 2 4-4',
        'llave' => 'M15 7a4 4 0 1 1 0 8 4 4 0 0 1 0-8ZM11 12H2M5 12v4M8 12v3',
        'flecha' => 'm6 9 6 6 6-6',
        'salir' => 'M9 21H4V3h5M14 8l4 4-4 4M8 12h13',
    ];
@endphp
<svg {{ $attributes->class(['navigation-icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="{{ $paths[$name] }}" /></svg>
