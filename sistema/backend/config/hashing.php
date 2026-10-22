<?php

return [
    'driver' => 'argon2id',
    'argon' => [
        'memory' => env('ARGON_MEMORY', 65536),
        'time' => env('ARGON_TIME', 3),
        'threads' => 1,
        'verify' => false,
    ],
    'rehash_on_login' => true,
];
