<?php

namespace Tests\Concerns;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Artisan;

/**
 * Aide pour les tests Feature : sème les rôles & permissions Spatie puis
 * fournit des raccourcis pour s'authentifier avec un rôle donné.
 */
trait SeedsPermissions
{
    protected function seedPermissions(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /**
     * Crée un utilisateur (avec rôle Spatie optionnel) et l'authentifie.
     */
    protected function actingAsUser(?string $roleName = null, array $attributes = []): User
    {
        /** @var User $user */
        $user = \Database\Factories\UserFactory::new()->create($attributes);

        if ($roleName) {
            $user->assignRole($roleName);
        }

        $this->actingAs($user);

        return $user;
    }

    protected function actingAsAdmin(array $attributes = []): User
    {
        return $this->actingAsUser(User::ROLE_ADMIN_NAME, array_merge(['role' => User::ROLE_ADMIN], $attributes));
    }

    /**
     * Authentifie un utilisateur ne possédant qu'une liste précise de permissions
     * (utile pour tester l'isolation des permissions).
     */
    protected function actingAsWithPermissions(array $permissions): User
    {
        /** @var User $user */
        $user = \Database\Factories\UserFactory::new()->create();
        $user->givePermissionTo($permissions);
        $this->actingAs($user);

        return $user;
    }
}
