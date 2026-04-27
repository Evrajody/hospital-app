<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
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
                'slug' => $role->name,
                'permissions' => $role->permissions->pluck('name')->toArray(),
                'users_count' => User::role($role->name)->count(),
                'created_at' => $role->created_at?->format('d/m/Y'),
            ]);

        $permissions = Permission::orderBy('name')
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'label' => self::permissionLabel($p->name),
                'module' => self::extractModule($p->name),
            ]);

        // Grouper les permissions par module avec libellés humains
        $permissionGroups = [];
        foreach ($permissions as $p) {
            $module = $p['module'];
            if (!isset($permissionGroups[$module])) {
                $permissionGroups[$module] = [
                    'key' => $module,
                    'label' => self::moduleLabel($module),
                    'permissions' => [],
                ];
            }
            $permissionGroups[$module]['permissions'][] = $p;
        }

        // Re-indexer en tableau pour le frontend
        $permissionGroups = array_values($permissionGroups);

        return Inertia::render('Admin/Roles', [
            'roles' => $roles,
            'permissions' => $permissions,
            'permissionGroups' => $permissionGroups,
        ]);
    }

    /**
     * Convertir un module technique en libellé humain.
     */
    private static function moduleLabel(string $module): string
    {
        $labels = [
            'fournisseurs' => 'Fournisseurs',
            'factures-fournisseurs' => 'Factures Fournisseurs',
            'reglements-fournisseurs' => 'Règlements Fournisseurs',
            'clients' => 'Clients',
            'factures-clients' => 'Factures Clients',
            'reglements-clients' => 'Règlements Clients',
            'plan-comptable' => 'Plan Comptable',
            'banques' => 'Banques',
            'rapports' => 'Rapports',
            'utilisateurs' => 'Utilisateurs',
            'roles' => 'Rôles & Permissions',
            'parametres' => 'Paramètres',
            'journal' => 'Journal d\'Activité',
        ];
        return $labels[$module] ?? ucfirst(str_replace('-', ' ', $module));
    }

    /**
     * Convertir un nom de permission en libellé humain.
     */
    private static function permissionLabel(string $name): string
    {
        $action = explode('.', $name)[1] ?? $name;
        $labels = [
            'voir' => 'Consulter',
            'creer' => 'Créer',
            'modifier' => 'Modifier',
            'supprimer' => 'Supprimer',
            'valider' => 'Valider',
            'imprimer' => 'Imprimer',
            'exporter' => 'Exporter',
        ];
        return $labels[$action] ?? ucfirst(str_replace(['-', '_'], ' ', $action));
    }

    private static function extractModule(string $permissionName): string
    {
        return explode('.', $permissionName)[0] ?? 'general';
    }

    /**
     * Toggle (activer/désactiver) une permission pour un rôle (API).
     */
    public function togglePermission(Request $request, int $id): JsonResponse
    {
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'permission' => ['required', 'string', 'exists:permissions,name'],
            'value' => ['required', 'boolean'],
        ]);

        if ($validated['value']) {
            $role->givePermissionTo($validated['permission']);
        } else {
            $role->revokePermissionTo($validated['permission']);
        }

        ActivityLog::log('update', 'role', "Permission '{$validated['permission']}' " . ($validated['value'] ? 'accordée' : 'retirée') . " au rôle {$role->name}", $role);

        return response()->json([
            'success' => true,
            'message' => 'Permission mise à jour',
            'permissions' => $role->fresh()->permissions->pluck('name')->toArray(),
        ]);
    }

    /**
     * Bulk : appliquer/retirer toutes les permissions d'un module pour un rôle (API).
     */
    public function bulkPermissions(Request $request, int $id): JsonResponse
    {
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
            'value' => ['required', 'boolean'],
        ]);

        if ($validated['value']) {
            foreach ($validated['permissions'] as $perm) {
                $role->givePermissionTo($perm);
            }
        } else {
            foreach ($validated['permissions'] as $perm) {
                $role->revokePermissionTo($perm);
            }
        }

        $action = $validated['value'] ? 'accordées' : 'retirées';
        $count = count($validated['permissions']);
        ActivityLog::log('update', 'role', "{$count} permission(s) {$action} au rôle {$role->name}", $role);

        return response()->json([
            'success' => true,
            'message' => "{$count} permission(s) mise(s) à jour",
            'permissions' => $role->fresh()->permissions->pluck('name')->toArray(),
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

        ActivityLog::log('create', 'role', "Création du rôle {$role->name}", $role);

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

        ActivityLog::log('update', 'role', "Modification du rôle {$role->name}", $role);

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

        $nom = $role->name;
        $role->delete();

        ActivityLog::log('delete', 'role', "Suppression du rôle {$nom}", null, ['name' => $nom]);

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
