<?php

namespace Tests\Feature;

use Database\Factories\BanqueFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsPermissions;
use Tests\TestCase;

class BanqueApiTest extends TestCase
{
    use RefreshDatabase;
    use SeedsPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();
    }

    public function test_creation_banque_refusee_sans_permission(): void
    {
        $this->actingAsWithPermissions(['banques.voir']);
        $this->postJson('/api/banques', ['nom' => 'Ma Banque'])->assertStatus(403);
    }

    public function test_creation_banque(): void
    {
        $this->actingAsWithPermissions(['banques.creer']);
        $this->postJson('/api/banques', ['nom' => 'BOA Bénin'])
            ->assertSuccessful()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('banques', ['nom' => 'BOA Bénin']);
    }

    public function test_nom_de_banque_unique(): void
    {
        $this->actingAsWithPermissions(['banques.creer']);
        BanqueFactory::new()->create(['nom' => 'Banque Unique']);

        $this->postJson('/api/banques', ['nom' => 'Banque Unique'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('nom');
    }

    public function test_suppression_banque(): void
    {
        $this->actingAsWithPermissions(['banques.supprimer']);
        $banque = BanqueFactory::new()->create();

        $this->deleteJson("/api/banques/{$banque->id}")->assertSuccessful();
    }
}
