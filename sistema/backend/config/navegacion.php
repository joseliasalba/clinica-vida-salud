<?php

return [
    'grupos' => [
        [
            'titulo' => 'Gestión clínica',
            'icono' => 'personas',
            'opciones' => [
                ['titulo' => 'Pacientes', 'icono' => 'personas', 'permiso' => 'pacientes.gestionar', 'ruta' => 'pacientes.index', 'activo' => ['pacientes.index', 'pacientes.create', 'pacientes.show', 'pacientes.edit']],
                ['titulo' => 'Historial de citas', 'icono' => 'reloj', 'permiso' => 'pacientes.gestionar', 'ruta' => 'historial.index', 'activo' => ['historial.*', 'pacientes.historial']],
                ['titulo' => 'Especialidades', 'icono' => 'salud', 'permiso' => 'medicos.gestionar', 'ruta' => 'especialidades.index', 'activo' => 'especialidades.*'],
                ['titulo' => 'Médicos', 'icono' => 'persona', 'permiso' => 'medicos.gestionar', 'ruta' => 'medicos.index', 'activo' => 'medicos.*'],
            ],
        ],
        [
            'titulo' => 'Citas y horarios',
            'icono' => 'calendario',
            'opciones' => [
                ['titulo' => 'Citas', 'icono' => 'calendario', 'permiso' => 'citas.gestionar', 'ruta' => 'citas.index', 'activo' => ['citas.index', 'citas.review', 'citas.receipt']],
                ['titulo' => 'Gestionar citas', 'icono' => 'calendario', 'permiso' => 'citas.gestionar', 'ruta' => 'citas.gestion', 'activo' => ['citas.gestion', 'citas.reprogramar.*', 'citas.cancelar.*', 'citas.asistencia.*']],
                ['titulo' => 'Horarios de atención', 'icono' => 'reloj', 'permiso' => 'horarios.gestionar', 'ruta' => 'horarios.index', 'activo' => 'horarios.*'],
                ['titulo' => 'Reservar cita', 'icono' => 'calendario', 'permiso' => 'citas.propias', 'ruta' => 'citas.index', 'activo' => ['citas.index', 'citas.review', 'citas.receipt']],
                ['titulo' => 'Mi historial', 'icono' => 'reloj', 'permiso' => 'citas.propias', 'ruta' => 'historial.index', 'activo' => ['historial.*', 'pacientes.historial']],
                ['titulo' => 'Mi agenda', 'icono' => 'calendario', 'permiso' => 'agenda.propia', 'ruta' => null],
                ['titulo' => 'Registrar asistencia', 'icono' => 'reloj', 'permiso' => 'agenda.propia', 'ruta' => 'citas.gestion', 'activo' => ['citas.gestion', 'citas.reprogramar.*', 'citas.cancelar.*', 'citas.asistencia.*']],
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
