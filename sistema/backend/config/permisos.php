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
    ],

    'roles' => [
        'administrador' => ['usuarios.gestionar', 'pacientes.gestionar', 'medicos.gestionar', 'horarios.gestionar', 'citas.gestionar'],
        'recepcion' => ['pacientes.gestionar', 'citas.gestionar'],
        'medico' => ['agenda.propia'],
        'paciente' => ['citas.propias', 'perfil.propio'],
    ],
];
