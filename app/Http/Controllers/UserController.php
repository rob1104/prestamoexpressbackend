<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        return response()->json(User::with('roles')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role'     => 'required|string|exists:roles,name',
        ]);
        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'active'   => true,
        ]);
        $user->assignRole($validated['role']);
        return response()->json(['message' => 'Usuario creado con éxito', 'user' => $user->load('roles')], 201);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8',
            'role'     => 'required|string|exists:roles,name',
        ]);
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        if (Auth::id() !== $user->id) {
            $user->syncRoles($validated['role']);
        }

        $oldRole = $user->getRoleNames()->first();
        $newRole = $request->role;

        if ($oldRole !== $newRole) {
            $user->syncRoles([$newRole]);

            // LOG MANUAL: Cambio de Jerarquía
            activity('Usuarios')
                ->performedOn($user)
                ->causedBy(auth()->user())
                ->withProperties([
                    'rol_anterior' => $oldRole,
                    'rol_nuevo' => $newRole
                ])
                ->log("Cambio de rol para el usuario: {$user->name}");
        }

        $user->save();
        return response()->json(['message' => 'Usuario actualizado', 'user' => $user->load('roles')]);
    }

    public function toggleStatus(User $user)
    {
        if (Auth::id() === $user->id) {
            return response()->json([
                'message' => 'No puedes desactivar tu propia cuenta.'
            ], 403);
        }

        $user->active = !$user->active;
        $user->save();

        $status = $user->active ? 'activado' : 'desactivado';
        return response()->json(['message' => "Usuario $status correctamente", 'active' => $user->active]);
    }

    public function destroy(User $user)
    {
        if (Auth::id() === $user->id) {
            return response()->json([
                'message' => 'No puedes eliminar tu propia cuenta.'
            ], 403);
        }

        $user->delete();
        return response()->json(['message' => 'Usuario eliminado correctamente']);
    }
}
