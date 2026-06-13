<?php

namespace Tests\Unit;

use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_cast_hashed_hache_le_mot_de_passe_a_la_creation(): void
    {
        $user = UserFactory::new()->create(['password' => 'secret-en-clair']);

        $this->assertNotSame('secret-en-clair', $user->password);
        $this->assertTrue(Hash::check('secret-en-clair', $user->password));
    }

    public function test_le_cast_hashed_ne_re_hache_pas_un_hash_existant(): void
    {
        $hash = Hash::make('deja-hache');
        $user = UserFactory::new()->create(['password' => $hash]);

        $this->assertSame($hash, $user->password);
    }

    public function test_is_admin_via_colonne_role(): void
    {
        $user = UserFactory::new()->create(['role' => User::ROLE_ADMIN]);
        $this->assertTrue($user->isAdmin());
    }

    public function test_is_admin_via_role_spatie(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $user = UserFactory::new()->create(['role' => User::ROLE_USER]);
        $user->assignRole(User::ROLE_ADMIN_NAME);

        $this->assertTrue($user->fresh()->isAdmin());
    }

    public function test_is_comptable_et_is_gestionnaire(): void
    {
        $this->assertTrue(UserFactory::new()->create(['role' => User::ROLE_COMPTABLE])->isComptable());
        $this->assertTrue(UserFactory::new()->create(['role' => User::ROLE_ADMIN])->isComptable());
        $this->assertFalse(UserFactory::new()->create(['role' => User::ROLE_USER])->isComptable());

        $this->assertTrue(UserFactory::new()->create(['role' => User::ROLE_GESTIONNAIRE])->isGestionnaire());
        $this->assertFalse(UserFactory::new()->create(['role' => User::ROLE_USER])->isGestionnaire());
    }

    public function test_role_libelle_accessor(): void
    {
        $this->assertSame('Administrateur', UserFactory::new()->create(['role' => User::ROLE_ADMIN])->role_libelle);
        $this->assertSame('Utilisateur', UserFactory::new()->create(['role' => 'inconnu'])->role_libelle);
    }

    public function test_scope_actif_ne_retourne_que_les_comptes_actifs(): void
    {
        UserFactory::new()->count(2)->create(['is_active' => true]);
        UserFactory::new()->inactif()->create();

        $this->assertSame(2, User::actif()->count());
    }

    public function test_get_roles_retourne_les_quatre_roles_legacy(): void
    {
        $roles = collect(User::getRoles())->pluck('value')->all();

        $this->assertEqualsCanonicalizing(
            [User::ROLE_ADMIN, User::ROLE_COMPTABLE, User::ROLE_GESTIONNAIRE, User::ROLE_USER],
            $roles
        );
    }
}
