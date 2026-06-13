<?php

namespace Tests\Feature;

use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_page_de_login_est_accessible(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_un_visiteur_est_redirige_vers_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_connexion_reussie_redirige_vers_le_dashboard(): void
    {
        $user = UserFactory::new()->create([
            'email' => 'user@hospital.bj',
            'password' => Hash::make('motdepasse'),
            'is_active' => true,
        ]);

        $this->post('/login', ['email' => 'user@hospital.bj', 'password' => 'motdepasse'])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_mauvais_identifiants_rejetes(): void
    {
        UserFactory::new()->create(['email' => 'user@hospital.bj', 'password' => Hash::make('bon')]);

        $this->from('/login')
            ->post('/login', ['email' => 'user@hospital.bj', 'password' => 'mauvais'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_compte_desactive_ne_peut_pas_se_connecter(): void
    {
        UserFactory::new()->inactif()->create([
            'email' => 'inactif@hospital.bj',
            'password' => Hash::make('motdepasse'),
        ]);

        $this->from('/login')
            ->post('/login', ['email' => 'inactif@hospital.bj', 'password' => 'motdepasse'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_validation_des_champs_de_login(): void
    {
        $this->from('/login')
            ->post('/login', ['email' => 'pas-un-email', 'password' => ''])
            ->assertSessionHasErrors(['email', 'password']);
    }

    public function test_deconnexion(): void
    {
        $user = UserFactory::new()->create();

        $this->actingAs($user)->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }
}
