<?php

namespace Tests\Feature;

use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_mise_a_jour_du_profil(): void
    {
        $user = UserFactory::new()->create(['name' => 'Ancien', 'email' => 'old@hospital.bj']);

        $this->actingAs($user)
            ->putJson('/profile', [
                'name' => 'Nouveau Nom',
                'email' => 'new@hospital.bj',
                'poste' => 'Comptable',
            ])->assertSuccessful()->assertJson(['success' => true]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Nouveau Nom',
            'email' => 'new@hospital.bj',
        ]);
    }

    public function test_changement_de_mot_de_passe(): void
    {
        $user = UserFactory::new()->create(['password' => Hash::make('AncienPass1!')]);

        $this->actingAs($user)
            ->putJson('/profile/password', [
                'current_password' => 'AncienPass1!',
                'password' => 'Nouveau!Pass2',
                'password_confirmation' => 'Nouveau!Pass2',
            ])->assertSuccessful();

        $this->assertTrue(Hash::check('Nouveau!Pass2', $user->fresh()->password));
    }

    public function test_mauvais_mot_de_passe_actuel_rejete(): void
    {
        $user = UserFactory::new()->create(['password' => Hash::make('AncienPass1!')]);

        $this->actingAs($user)
            ->putJson('/profile/password', [
                'current_password' => 'FauxPass',
                'password' => 'Nouveau!Pass2',
                'password_confirmation' => 'Nouveau!Pass2',
            ])->assertStatus(422)->assertJson(['success' => false]);

        $this->assertTrue(Hash::check('AncienPass1!', $user->fresh()->password));
    }

    public function test_confirmation_de_mot_de_passe_requise(): void
    {
        $user = UserFactory::new()->create(['password' => Hash::make('AncienPass1!')]);

        $this->actingAs($user)
            ->putJson('/profile/password', [
                'current_password' => 'AncienPass1!',
                'password' => 'Nouveau!Pass2',
                'password_confirmation' => 'PasPareil9!',
            ])->assertStatus(422)->assertJsonValidationErrors('password');
    }
}
