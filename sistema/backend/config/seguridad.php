<?php

return [
    'omitir_verificacion_local' => (bool) env('OMITIR_VERIFICACION_CORREO_LOCAL', false),
    'despliegue_vercel' => env('VERCEL') === '1',
    'avisos_resultados_habilitados' => (bool) env('AVISOS_RESULTADOS_HABILITADOS', false),
    'avisos_resultados_secret' => env('AVISOS_RESULTADOS_SECRET'),
    'intentos_login' => 5,
    'espera_login' => 900,
    'inactividad' => 1800,
    'claves_comunes' => ['12345678', 'password', 'qwerty12', 'clinica1', '123456789012345', 'passwordpassword', 'password123456789', 'clinica123456789', 'qwertyuiopasdfgh', '12345678901234567890'],
];
