<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function getRolesName()
    {
        return response()->json(Role::all()->pluck('name'));
    }

    public function index()
    {
        return response()->json(Role::with('permissions')->get());
    }

    public function getAllPermissions()
    {
        return response()->json(Permission::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|unique:roles,name',
            'permissions' => 'array'
            ]);

        $role = Role::create(['name' => $validated['name'], 'guard_name' => 'web']);

        if(!empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        activity('Seguridad')
            ->performedOn($role)
            ->causedBy(auth()->user())
            ->withProperties(['permisos_asignados' => $validated['permissions']])
            ->log("Creación de nuevo rol: {$role->name}");

        return response()->json(
            ['message' => 'Rol creado correctamente',
                'role' => $role->load('permissions')]);
    }

    public function update(Request $request, Role $role)
    {
        // Evitar modificar el rol Administrador principal por seguridad
        if ($role->name === 'Administrador') {
            return response()->json(['message' => 'El rol Administrador es vital y no puede ser modificado.'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|unique:roles,name,' . $role->id,
            'permissions' => 'array'
        ]);

        $oldPermissions = $role->permissions->pluck('name')->toArray();
        $role->update(['name' => $validated['name']]);
        $role->syncPermissions($validated['permissions']);

        activity('Seguridad')
            ->performedOn($role)
            ->causedBy(auth()->user())
            ->withProperties([
                'antes' => $oldPermissions,
                'despues' => $validated['permissions']
            ])
            ->log("Permisos actualizados para el rol: {$role->name}");

        return response()->json(['message' => 'Rol actualizado', 'role' => $role->load('permissions')]);
    }

    public function destroy(Role $role)
    {
        // 1.- Protección de Integridad: No borrar el rol principal Administrador
        if ($role->name === 'Administrador') {
            return response()->json([
                'message' => '¡Error de seguridad! El rol Administrador es vital para el sistema y no puede ser eliminado.'
            ], 403);
        }

        // 2. Validación de Relaciones: Verificar si tiene usuarios asignados
        // Spatie usa la relación 'users' a través de la tabla model_has_roles
        $userCount = $role->users()->count();
        if ($userCount > 0) {
            return response()->json([
                'message' => "No se puede eliminar el rol '{$role->name}' porque tiene {$userCount} usuario(s) asignado(s). Por favor, mueva a los usuarios a otro rol primero."
            ], 422);
        }

        $role->delete();
        return response()->json(['message' => 'El rol ha sido eliminado correctamente del sistema.']);
    }
}
