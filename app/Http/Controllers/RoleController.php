<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(): \Inertia\Response
    {
        $roles = Role::with('permissions')
            ->orderBy('name')
            ->get()
            ->map(fn($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name')->toArray(),
                'users_count' => User::role($role->name)->count(),
                'created_at' => $role->created_at?->format('d/m/Y'),
            ]);

        $permissions = Permission::orderBy('name')
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
            ]);

        // Grouper les permissions par module
        $permissionGroups = [];
        foreach ($permissions as $p) {
            $parts = explode('.', $p['name']);
            $module = $parts[0] ?? 'general';
            $permissionGroups[$module][] = $p;
        }

        return Inertia::render('Admin/Roles', [
            'roles' => $roles,
            'permissions' => $permissions,
            'permissionGroups' => $permissionGroups,
        ]);
    }

    public function storeRole(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role = Role::create(['name' => $validated['name'], 'guard_name' => 'web']);

        if (!empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Rôle créé avec succès',
        ]);
    }

    public function updateRole(Request $request, int $id): JsonResponse
    {
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($role->id)],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role->update(['name' => $validated['name']]);
        $role->syncPermissions($validated['permissions'] ?? []);

        return response()->json([
            'success' => true,
            'message' => 'Rôle modifié avec succès',
        ]);
    }

    public function destroyRole(int $id): JsonResponse
    {
        $role = Role::findOrFail($id);

        if (User::role($role->name)->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Ce rôle est assigné à des utilisateurs et ne peut pas être supprimé',
            ], 422);
        }

        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'Rôle supprimé avec succès',
        ]);
    }

    public function storePermission(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:permissions,name'],
        ]);

        Permission::create(['name' => $validated['name'], 'guard_name' => 'web']);

        return response()->json([
            'success' => true,
            'message' => 'Permission créée avec succès',
        ]);
    }

    public function destroyPermission(int $id): JsonResponse
    {
        $permission = Permission::findOrFail($id);

        $permission->delete();

        return response()->json([
            'success' => true,
            'message' => 'Permission supprimée avec succès',
        ]);
    }
}
