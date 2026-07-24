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

    public function test_paiement_total_utilise_la_date_du_reglement_comme_date_solde(): void
    {
        $this->actingAsWithPermissions(['reglements-clients.creer']);
        $facture = FactureClientFactory::new()->create([
            'date_facture' => '2026-05-01',
            'montant' => 100000,
            'ristourne' => 0,
        ]);

        $this->postJson('/api/reglements-clients', [
            'facture_id' => $facture->id,
            'date_reglement' => '2026-05-10',
            'montant' => 100000,
        ])->assertCreated();

        $facture->refresh();
        $this->assertSame(FactureClient::STATUT_PAYEE, $facture->statut);
        $this->assertSame('2026-05-10', $facture->date_solde?->format('Y-m-d'));
    }

    public function test_reglement_anterieur_a_la_facture_est_rejete(): void
    {
        $this->actingAsWithPermissions(['reglements-clients.creer']);
        $facture = FactureClientFactory::new()->create(['date_facture' => '2026-05-10']);

        $this->postJson('/api/reglements-clients', [
            'facture_id' => $facture->id,
            'date_reglement' => '2026-05-09',
            'montant' => 50000,
        ])->assertUnprocessable()->assertJsonValidationErrors('date_reglement');
    }

    public function test_montant_superieur_au_net_cree_un_solde_negatif_et_solde_la_facture(): void
    {
        $this->actingAsWithPermissions(['reglements-clients.creer']);
        $facture = FactureClientFactory::new()->create([
            'date_facture' => '2026-05-01',
            'montant' => 100000,
            'ristourne' => 0,
        ]);

        $this->postJson('/api/reglements-clients', [
            'facture_id' => $facture->id,
            'date_reglement' => '2026-05-10',
            'montant' => 150000,
        ])->assertCreated()->assertJson(['success' => true]);

        $facture->refresh();
        $this->assertSame(FactureClient::STATUT_PAYEE, $facture->statut);
        $this->assertEquals(-50000, $facture->reste_a_payer);
        $this->assertSame('2026-05-10', $facture->date_solde?->format('Y-m-d'));
    }

    public function test_nouveau_reglement_refuse_si_facture_deja_soldee(): void
    {
        $this->actingAsWithPermissions(['reglements-clients.creer']);
        $facture = FactureClientFactory::new()->create([
            'date_facture' => '2026-05-01',
            'montant' => 100000,
            'ristourne' => 0,
        ]);

        $payload = [
            'facture_id' => $facture->id,
            'date_reglement' => '2026-05-10',
            'montant' => 120000,
        ];
        $this->postJson('/api/reglements-clients', $payload)->assertCreated();

        $payload['date_reglement'] = '2026-05-11';
        $payload['montant'] = 1000;
        $this->postJson('/api/reglements-clients', $payload)
            ->assertUnprocessable()
            ->assertJson(['success' => false]);

        $this->assertEquals(120000, $facture->reglements()->sum('montant'));
    }

    public function test_imputation_avance_d_un_autre_client_rejetee(): void
    {
        $this->actingAsWithPermissions(['reglements-clients.creer']);
        $facture = FactureClientFactory::new()->create([
            'date_facture' => '2026-05-01',
            'montant' => 100000,
            'ristourne' => 0,
        ]);
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
