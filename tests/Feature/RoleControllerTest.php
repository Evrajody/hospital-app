<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Concerns\SeedsPermissions;
use Tests\TestCase;

class RoleControllerTest extends TestCase
{
    use RefreshDatabase;
    use SeedsPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();
    }

    public function test_un_admin_peut_accorder_n_importe_quelle_permission(): void
    {
        $this->actingAsAdmin();
        $role = Role::create(['name' => 'Test Role', 'guard_name' => 'web']);

        // L'admin ne possède pas lui-même 'fournisseurs.creer' mais peut l'accorder.
        $this->patchJson("/api/roles/{$role->id}/permission", [
            'permission' => 'fournisseurs.creer',
            'value' => true,
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertTrue($role->fresh()->hasPermissionTo('fournisseurs.creer'));
    }

    public function test_un_non_admin_ne_peut_accorder_que_ses_propres_permissions(): void
    {
        // Utilisateur qui peut gérer les rôles mais ne détient pas 'fournisseurs.supprimer'
        $this->actingAsWithPermissions(['roles.modifier', 'fournisseurs.voir']);
        $role = Role::create(['name' => 'Limité', 'guard_name' => 'web']);

        // Permission qu'il ne possède pas → refus 403
        $this->patchJson("/api/roles/{$role->id}/permission", [
            'permission' => 'fournisseurs.supprimer',
            'value' => true,
        ])->assertStatus(403);

        // Permission qu'il possède → autorisé
        $this->patchJson("/api/roles/{$role->id}/permission", [
            'permission' => 'fournisseurs.voir',
            'value' => true,
        ])->assertOk();

        $this->assertTrue($role->fresh()->hasPermissionTo('fournisseurs.voir'));
        $this->assertFalse($role->fresh()->hasPermissionTo('fournisseurs.supprimer'));
    }

    public function test_le_role_administrateur_est_protege_en_modification(): void
    {
        $this->actingAsAdmin();
        $admin = Role::where('name', User::ROLE_ADMIN_NAME)->first();

        $this->putJson("/api/roles/{$admin->id}", [
            'name' => 'Renommé',
            'permissions' => [],
        ])->assertStatus(403);

        $this->assertSame(User::ROLE_ADMIN_NAME, $admin->fresh()->name);
    }

    public function test_le_role_administrateur_ne_peut_pas_etre_supprime(): void
    {
        $this->actingAsAdmin();
        $admin = Role::where('name', User::ROLE_ADMIN_NAME)->first();

        $this->deleteJson("/api/roles/{$admin->id}")->assertStatus(403);

        $this->assertDatabaseHas('roles', ['name' => User::ROLE_ADMIN_NAME]);
    }

    public function test_creation_et_suppression_d_un_role(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/roles', [
            'name' => 'Auditeur',
            'permissions' => ['journal.voir'],
        ])->assertOk()->assertJson(['success' => true]);

        $role = Role::where('name', 'Auditeur')->firstOrFail();
        $this->assertTrue($role->hasPermissionTo('journal.voir'));

        $this->deleteJson("/api/roles/{$role->id}")->assertOk();
        $this->assertDatabaseMissing('roles', ['name' => 'Auditeur']);
    }

    public function test_un_role_assigne_a_des_utilisateurs_ne_peut_pas_etre_supprime(): void
    {
        $this->actingAsAdmin();
        $role = Role::create(['name' => 'Occupé', 'guard_name' => 'web']);
        \Database\Factories\UserFactory::new()->create()->assignRole($role);

        $this->deleteJson("/api/roles/{$role->id}")->assertStatus(422);
        $this->assertDatabaseHas('roles', ['name' => 'Occupé']);
    }

    public function test_bulk_permissions_par_module(): void
    {
        $this->actingAsAdmin();
        $role = Role::create(['name' => 'GestBanques', 'guard_name' => 'web']);

        $this->patchJson("/api/roles/{$role->id}/permissions/bulk", [
            'permissions' => ['banques.voir', 'banques.creer', 'banques.modifier', 'banques.supprimer'],
            'value' => true,
        ])->assertOk();

        $this->assertSame(4, $role->fresh()->permissions->count());
    }

    public function test_acces_refuse_sans_la_permission_roles_voir(): void
    {
        $this->actingAsWithPermissions(['fournisseurs.voir']);

        // Requête web (Inertia) : l'exception handler redirige avec un message d'erreur.
        $this->get('/roles')->assertStatus(302);

        // Requête JSON : le même handler renvoie un 403.
        $this->getJson('/roles')->assertStatus(403);
    }
}
