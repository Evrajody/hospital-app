<?php

namespace Tests\Feature;

use App\Models\FactureClient;
use Database\Factories\ClientFactory;
use Database\Factories\FactureClientFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsPermissions;
use Tests\TestCase;

class FactureClientApiTest extends TestCase
{
    use RefreshDatabase;
    use SeedsPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();
    }

    public function test_creation_refusee_sans_permission(): void
    {
        $this->actingAsWithPermissions(['factures-clients.voir']);
        $client = ClientFactory::new()->create();

        $this->postJson('/api/factures-clients', [
            'reference' => '0001/05/26',
            'date_facture' => '2026-05-01',
            'montant' => 50000,
            'client_id' => $client->id,
        ])->assertStatus(403);
    }

    public function test_creation_calcule_le_reste_a_payer(): void
    {
        $this->actingAsWithPermissions(['factures-clients.creer']);
        $client = ClientFactory::new()->create();

        $this->postJson('/api/factures-clients', [
            'reference' => '0001/05/26',
            'date_facture' => '2026-05-01',
            'montant' => 100000,
            'ristourne' => 10000,
            'client_id' => $client->id,
        ])->assertCreated()->assertJson(['success' => true]);

        $this->assertDatabaseHas('factures_clients', [
            'reference' => '0001/05/26',
            'reste_a_payer' => 90000,
        ]);
    }

    public function test_reference_unique(): void
    {
        $this->actingAsWithPermissions(['factures-clients.creer']);
        $client = ClientFactory::new()->create();
        FactureClientFactory::new()->create(['reference' => '0001/05/26']);

        $this->postJson('/api/factures-clients', [
            'reference' => '0001/05/26',
            'date_facture' => '2026-05-01',
            'montant' => 50000,
            'client_id' => $client->id,
        ])->assertStatus(422)->assertJsonValidationErrors('reference');
    }

    public function test_suppression(): void
    {
        $this->actingAsWithPermissions(['factures-clients.supprimer']);
        $facture = FactureClientFactory::new()->create();

        $this->deleteJson("/api/factures-clients/{$facture->id}")->assertSuccessful();
        $this->assertSoftDeleted('factures_clients', ['id' => $facture->id]);
    }
}
