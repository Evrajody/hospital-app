<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsPermissions;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;
    use SeedsPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Nouvel Agent',
            'email' => 'agent@hospital.bj',
            'password' => 'Sup3r!Secret',
            'is_active' => true,
            'roles' => [User::ROLE_GEST_CLIENTS_NAME],
        ], $overrides);
    }

    public function test_creation_refusee_sans_permission(): void
    {
        $this->actingAsWithPermissions(['utilisateurs.voir']);
        $this->postJson('/api/utilisateurs', $this->payload())->assertStatus(403);
    }

    public function test_creation_avec_role(): void
    {
        $this->actingAsWithPermissions(['utilisateurs.creer']);

        $this->postJson('/api/utilisateurs', $this->payload())
            ->assertSuccessful()->assertJson(['success' => true]);

        $user = User::where('email', 'agent@hospital.bj')->firstOrFail();
        $this->assertTrue($user->hasRole(User::ROLE_GEST_CLIENTS_NAME));
    }

    public function test_mot_de_passe_faible_rejete(): void
    {
        $this->actingAsWithPermissions(['utilisateurs.creer']);

        $this->postJson('/api/utilisateurs', $this->payload(['password' => '1234']))
            ->assertStatus(422)->assertJsonValidationErrors('password');
    }

    public function test_email_unique(): void
    {
        $this->actingAsWithPermissions(['utilisateurs.creer']);
        UserFactory::new()->create(['email' => 'agent@hospital.bj']);

        $this->postJson('/api/utilisateurs', $this->payload())
            ->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_on_ne_peut_pas_supprimer_son_propre_compte(): void
    {
        $me = $this->actingAsWithPermissions(['utilisateurs.supprimer']);

        $this->deleteJson("/api/utilisateurs/{$me->id}")
            ->assertStatus(422)->assertJson(['success' => false]);

        $this->assertDatabaseHas('users', ['id' => $me->id, 'deleted_at' => null]);
    }

    public function test_suppression_d_un_autre_utilisateur(): void
    {
        $this->actingAsWithPermissions(['utilisateurs.supprimer']);
        $autre = UserFactory::new()->create();

        $this->deleteJson("/api/utilisateurs/{$autre->id}")->assertSuccessful();
        $this->assertSoftDeleted('users', ['id' => $autre->id]);
    }

    public function test_toggle_active(): void
    {
        $this->actingAsWithPermissions(['utilisateurs.modifier']);
        $autre = UserFactory::new()->create(['is_active' => true]);

        $this->patchJson("/api/utilisateurs/{$autre->id}/toggle-active")->assertSuccessful();
        $this->assertFalse($autre->fresh()->is_active);
    }

    public function test_on_ne_peut_pas_se_desactiver_soi_meme(): void
    {
        $me = $this->actingAsWithPermissions(['utilisateurs.modifier']);

        $this->patchJson("/api/utilisateurs/{$me->id}/toggle-active")
            ->assertStatus(422);
        $this->assertTrue($me->fresh()->is_active);
    }
}
