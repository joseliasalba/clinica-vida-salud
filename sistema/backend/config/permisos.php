<?php

return [
    'catalogo' => [
        'usuarios.gestionar' => 'Administrar usuarios, roles y permisos',
        'pacientes.gestionar' => 'Gestionar pacientes desde recepción',
        'medicos.gestionar' => 'Gestionar médicos y especialidades',
        'horarios.gestionar' => 'Configurar horarios de atención',
        'citas.gestionar' => 'Gestionar citas desde recepción',
        'citas.propias' => 'Consultar y reservar citas propias',
        'agenda.propia' => 'Consultar la agenda del médico autenticado',
        'perfil.propio' => 'Actualizar el perfil del paciente autenticado',
        'estudios.solicitar' => 'Solicitar estudios para consultas del médico vinculado',
        'estudios.publicar' => 'Cargar y publicar resultados de estudios propios',
        'estudios.propios' => 'Consultar estudios y resultados publicados del paciente vinculado',
        'recordatorios.propios' => 'Gestionar preferencias y recordatorios del paciente vinculado',
    ],

    'roles' => [
        'administrador' => ['usuarios.gestionar', 'pacientes.gestionar', 'medicos.gestionar', 'horarios.gestionar', 'citas.gestionar'],
        'recepcion' => ['pacientes.gestionar', 'citas.gestionar'],
        'medico' => ['agenda.propia', 'estudios.solicitar', 'estudios.publicar'],
        'paciente' => ['citas.propias', 'perfil.propio', 'estudios.propios', 'recordatorios.propios'],
    ],
];
