<?php

namespace Tests\Feature;

use App\Models\Fournisseur;
use Database\Factories\FactureFournisseurFactory;
use Database\Factories\FournisseurFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsPermissions;
use Tests\TestCase;

class FournisseurApiTest extends TestCase
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
            'nom' => 'Nouveau Fournisseur',
            'type_fournisseur' => 'medicaments',
            'pays' => 'BJ',
            'ifu' => '1234567890123',
        ], $overrides);
    }

    public function test_creation_refusee_sans_permission(): void
    {
        $this->actingAsWithPermissions(['fournisseurs.voir']);

        $this->postJson('/api/fournisseurs', $this->payload())->assertStatus(403);
        $this->assertDatabaseCount('fournisseurs', 0);
    }

    public function test_creation_autorisee_avec_permission(): void
    {
        $this->actingAsWithPermissions(['fournisseurs.creer']);

        $this->postJson('/api/fournisseurs', $this->payload())
            ->assertCreated()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('fournisseurs', ['nom' => 'Nouveau Fournisseur']);
    }

    public function test_validation_ifu_obligatoire_et_format(): void
    {
        $this->actingAsWithPermissions(['fournisseurs.creer']);

        $this->postJson('/api/fournisseurs', $this->payload(['ifu' => '123']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('ifu');
    }

    public function test_modification(): void
    {
        $this->actingAsWithPermissions(['fournisseurs.modifier']);
        $fournisseur = FournisseurFactory::new()->create(['nom' => 'Ancien Nom']);

        $this->putJson("/api/fournisseurs/{$fournisseur->id}", $this->payload(['nom' => 'Nom Modifié']))
            ->assertOk();

        $this->assertDatabaseHas('fournisseurs', ['id' => $fournisseur->id, 'nom' => 'Nom Modifié']);
    }

    public function test_suppression(): void
    {
        $this->actingAsWithPermissions(['fournisseurs.supprimer']);
        $fournisseur = FournisseurFactory::new()->create();

        $this->deleteJson("/api/fournisseurs/{$fournisseur->id}")->assertOk();

        $this->assertSoftDeleted('fournisseurs', ['id' => $fournisseur->id]);
    }

    public function test_suppression_refusee_si_factures_liees(): void
    {
        $this->actingAsWithPermissions(['fournisseurs.supprimer']);
        $fournisseur = FournisseurFactory::new()->create();
        FactureFournisseurFactory::new()->create(['fournisseur_id' => $fournisseur->id]);

        $this->deleteJson("/api/fournisseurs/{$fournisseur->id}")->assertStatus(422);
        $this->assertDatabaseHas('fournisseurs', ['id' => $fournisseur->id, 'deleted_at' => null]);
    }

    public function test_index_accessible_avec_permission_voir(): void
    {
        $this->actingAsWithPermissions(['fournisseurs.voir']);
        FournisseurFactory::new()->count(3)->create();

        $this->get('/fournisseurs')->assertOk();
    }
}
