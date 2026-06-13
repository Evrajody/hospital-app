<?php

namespace Tests\Feature;

use App\Models\FactureClient;
use Database\Factories\AvanceClientFactory;
use Database\Factories\FactureClientFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsPermissions;
use Tests\TestCase;

class ReglementClientApiTest extends TestCase
{
    use RefreshDatabase;
    use SeedsPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();
    }

    private function facture(int $montant = 100000): FactureClient
    {
        return FactureClientFactory::new()->create(['montant' => $montant, 'ristourne' => 0]);
    }

    public function test_creation_refusee_sans_permission(): void
    {
        $this->actingAsWithPermissions(['reglements-clients.voir']);
        $facture = $this->facture();

        $this->postJson('/api/reglements-clients', [
            'facture_id' => $facture->id,
            'date_reglement' => '2026-05-10',
            'montant' => 50000,
        ])->assertStatus(403);
    }

    public function test_creation_met_a_jour_la_facture(): void
    {
        $this->actingAsWithPermissions(['reglements-clients.creer']);
        $facture = $this->facture(100000);

        $this->postJson('/api/reglements-clients', [
            'facture_id' => $facture->id,
            'date_reglement' => '2026-05-10',
            'montant' => 60000,
            'institution' => 'BOA',
            'reference_cheque' => '1234567',
        ])->assertCreated()->assertJson(['success' => true]);

        $facture->refresh();
        $this->assertEquals(60000, $facture->montant_paye);
        $this->assertSame(FactureClient::STATUT_PARTIELLEMENT_PAYEE, $facture->statut);
    }

    public function test_montant_superieur_au_reste_a_payer_rejete(): void
    {
        $this->actingAsWithPermissions(['reglements-clients.creer']);
        $facture = $this->facture(100000);

        $this->postJson('/api/reglements-clients', [
            'facture_id' => $facture->id,
            'date_reglement' => '2026-05-10',
            'montant' => 150000,
        ])->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_imputation_avance_d_un_autre_client_rejetee(): void
    {
        $this->actingAsWithPermissions(['reglements-clients.creer']);
        $facture = $this->facture(100000);
        // Avance appartenant à un autre client
        $avanceAutreClient = AvanceClientFactory::new()->create(['montant' => 200000]);

        $this->postJson('/api/reglements-clients', [
            'facture_id' => $facture->id,
            'date_reglement' => '2026-05-10',
            'montant' => 50000,
            'avance_id' => $avanceAutreClient->id,
        ])->assertStatus(422)->assertJson(['success' => false]);
    }
}
