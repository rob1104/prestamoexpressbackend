<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CancelBoletaPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpiar caché de permisos
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear el permiso
        $permiso = Permission::firstOrCreate(['name' => 'cancelar boletas']);

        // Asignarlo al Super Admin por defecto
        $role = Role::where('name', 'Super Admin')->first();
        if ($role) {
            $role->givePermissionTo($permiso);
        }
    }
}
