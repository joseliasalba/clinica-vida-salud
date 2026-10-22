<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    private static bool $schemaPrepared = false;

    public function createApplication(): Application
    {
        $app = parent::createApplication();

        if (config('database.default') !== 'pgsql_testing'
            || config('database.connections.pgsql_testing.search_path') !== 'clinica_testing') {
            throw new RuntimeException('Las pruebas requieren PostgreSQL y el esquema aislado clinica_testing.');
        }

        if (! self::$schemaPrepared) {
            DB::statement('CREATE SCHEMA IF NOT EXISTS clinica_testing');

            if (DB::scalar('SELECT current_schema()') !== 'clinica_testing') {
                throw new RuntimeException('La conexión de pruebas no está aislada.');
            }

            if (Artisan::call('migrate', ['--force' => true, '--no-interaction' => true]) !== 0) {
                throw new RuntimeException(Artisan::output());
            }

            self::$schemaPrepared = true;
        }

        return $app;
    }
}
