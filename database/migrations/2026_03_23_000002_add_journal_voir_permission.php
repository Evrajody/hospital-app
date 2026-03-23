<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ajouter la permission journal.voir
        DB::table('permissions')->insertOrIgnore([
            'name' => 'journal.voir',
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // L'attribuer au rôle Administrateur
        $permissionId = DB::table('permissions')->where('name', 'journal.voir')->value('id');
        $adminRoleId = DB::table('roles')->where('name', 'Administrateur')->value('id');

        if ($permissionId && $adminRoleId) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $permissionId,
                'role_id' => $adminRoleId,
            ]);
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('name', 'journal.voir')->value('id');

        if ($permissionId) {
            DB::table('role_has_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }
    }
};
