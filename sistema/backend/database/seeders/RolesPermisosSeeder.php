<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesPermisosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach (config('permisos.catalogo') as $codigo => $descripcion) {
                DB::table('permisos')->insertOrIgnore([
                    'codigo' => $codigo,
                    'accion' => $codigo,
                    'descripcion' => $descripcion,
                    'activo' => true,
                ]);
            }

            foreach (config('permisos.roles') as $nombre => $permisos) {
                DB::table('roles')->insertOrIgnore([
                    'nombre' => $nombre,
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $rolId = DB::table('roles')->where('nombre', $nombre)->value('id');

                foreach ($permisos as $codigo) {
                    DB::table('rol_permiso')->insertOrIgnore([
                        'rol_id' => $rolId,
                        'permiso_id' => DB::table('permisos')->where('codigo', $codigo)->value('id'),
                        'activo' => true,
                    ]);
                }
            }
        });
    }
}
