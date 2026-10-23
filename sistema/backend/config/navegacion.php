<?php

return [
    'grupos' => [
        [
            'titulo' => 'Gestión clínica',
            'icono' => 'personas',
            'opciones' => [
                ['titulo' => 'Pacientes', 'icono' => 'personas', 'permiso' => 'pacientes.gestionar', 'ruta' => 'pacientes.index', 'activo' => 'pacientes.*'],
                ['titulo' => 'Historial de citas', 'icono' => 'reloj', 'permiso' => 'pacientes.gestionar', 'ruta' => 'historial.index', 'activo' => 'historial.*'],
                ['titulo' => 'Especialidades', 'icono' => 'salud', 'permiso' => 'medicos.gestionar', 'ruta' => 'especialidades.index', 'activo' => 'especialidades.*'],
                ['titulo' => 'Médicos', 'icono' => 'persona', 'permiso' => 'medicos.gestionar', 'ruta' => null],
            ],
        ],
        [
            'titulo' => 'Citas y horarios',
            'icono' => 'calendario',
            'opciones' => [
                ['titulo' => 'Citas', 'icono' => 'calendario', 'permiso' => 'citas.gestionar', 'ruta' => 'citas.index', 'activo' => 'citas.*'],
                ['titulo' => 'Horarios de atención', 'icono' => 'reloj', 'permiso' => 'horarios.gestionar', 'ruta' => null],
                ['titulo' => 'Mis citas', 'icono' => 'calendario', 'permiso' => 'citas.propias', 'ruta' => null],
                ['titulo' => 'Mi historial', 'icono' => 'reloj', 'permiso' => 'citas.propias', 'ruta' => 'historial.index', 'activo' => 'historial.*'],
                ['titulo' => 'Mi agenda', 'icono' => 'calendario', 'permiso' => 'agenda.propia', 'ruta' => null],
                ['titulo' => 'Registrar asistencia', 'icono' => 'reloj', 'permiso' => 'agenda.propia', 'ruta' => 'citas.index', 'activo' => 'citas.*'],
            ],
        ],
        [
            'titulo' => 'Usuarios y permisos',
            'icono' => 'escudo',
            'opciones' => [
                ['titulo' => 'Usuarios', 'icono' => 'personas', 'permiso' => 'usuarios.gestionar', 'ruta' => 'usuarios.index', 'activo' => 'usuarios.*'],
                ['titulo' => 'Roles', 'icono' => 'escudo', 'permiso' => 'usuarios.gestionar', 'ruta' => 'roles.index', 'activo' => 'roles.*'],
                ['titulo' => 'Permisos', 'icono' => 'llave', 'permiso' => 'usuarios.gestionar', 'ruta' => 'permisos.index', 'activo' => 'permisos.*'],
            ],
        ],
        [
            'titulo' => 'Mi cuenta',
            'icono' => 'persona',
            'opciones' => [
                ['titulo' => 'Mi perfil', 'icono' => 'persona', 'permiso' => 'perfil.propio', 'ruta' => null],
            ],
        ],
    ],
];
