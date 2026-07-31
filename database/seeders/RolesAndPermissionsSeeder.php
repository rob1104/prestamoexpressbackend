<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Clientes
            'ver clientes',
            'crear clientes',
            'editar clientes',
            'eliminar clientes',
            
            // Boletas
            'ver boletas',
            'crear boletas',
            'editar boletas',
            'eliminar boletas',
            'imprimir boletas',
            
            // Ventas
            'ventas joyeria',
            'ventas electronicos',
            
            // Caja y Movimientos
            'ver caja',
            'realizar movimientos caja',
            'cierre diario',
            
            // Reportes
            'ver reportes',
            
            // Configuracion y Parametros
            'configurar parametros',
            'configurar cotizacion oro',
            'gestionar catalogos joyeria',
            'gestionar conceptos flujo',
            
            // Sistema
            'ver usuarios',
            'crear usuarios',
            'editar usuarios',
            'eliminar usuarios',
            'ver roles',
            'crear roles',
            'editar roles',
            'eliminar roles',
            
            // Logs y BD
            'ver logs',
            'database.backup'
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Crear rol Administrador y asignar todos los permisos
        $roleAdmin = Role::firstOrCreate(['name' => 'Administrador']);
        $roleAdmin->syncPermissions(Permission::all());
    }
}
