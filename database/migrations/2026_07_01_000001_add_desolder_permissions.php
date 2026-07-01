<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ajoute les permissions dédiées « annuler le solde » d'une facture
 * (factures-fournisseurs.desolder / factures-clients.desolder) et les accorde à
 * tout rôle qui possède déjà la permission .modifier correspondante.
 */
return new class extends Migration
{
    private array $map = [
        'factures-fournisseurs.desolder' => 'factures-fournisseurs.modifier',
        'factures-clients.desolder' => 'factures-clients.modifier',
    ];

    public function up(): void
    {
        foreach ($this->map as $perm => $basedOn) {
            DB::table('permissions')->insertOrIgnore([
                'name' => $perm,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $permId = DB::table('permissions')->where('name', $perm)->value('id');
            $baseId = DB::table('permissions')->where('name', $basedOn)->value('id');
            if (! $permId || ! $baseId) {
                continue;
            }

            // Accorder à chaque rôle qui a déjà la permission .modifier correspondante.
            $roleIds = DB::table('role_has_permissions')->where('permission_id', $baseId)->pluck('role_id');
            foreach ($roleIds as $roleId) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => $permId,
                    'role_id' => $roleId,
                ]);
            }
        }

        // Vider le cache spatie/permission pour prise en compte immédiate.
        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        foreach (array_keys($this->map) as $perm) {
            $permId = DB::table('permissions')->where('name', $perm)->value('id');
            if ($permId) {
                DB::table('role_has_permissions')->where('permission_id', $permId)->delete();
                DB::table('permissions')->where('id', $permId)->delete();
            }
        }
        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
