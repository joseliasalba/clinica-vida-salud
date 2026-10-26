<?php

$temporaryDirectory = sys_get_temp_dir().'/clinica';

foreach ([$temporaryDirectory, $temporaryDirectory.'/views'] as $directory) {
    if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
        throw new RuntimeException('No se pudo preparar el directorio temporal.');
    }
}

require __DIR__.'/../sistema/backend/public/index.php';
