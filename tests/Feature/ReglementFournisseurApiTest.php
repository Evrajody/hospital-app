<?php

namespace Tests\Feature;

use App\Models\FactureFournisseur;
use Database\Factories\FactureFournisseurFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsPermissions;
use Tests\TestCase;

class ReglementFournisseurApiTest extends TestCase
{
    use RefreshDatabase;
    use SeedsPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();
    }

    private function facture(): FactureFournisseur
    {
        return FactureFournisseurFactory::new()->create([
            'montant_facture' => 100000,
            'assujetti_tva' => false,
            'statut' => FactureFournisseur::STATUT_VALIDEE,
        ]);
    }

    public function test_creation_refusee_sans_permission(): void
    {
        $this->actingAsWithPermissions(['reglements-fournisseurs.voir']);
        $facture = $this->facture();

        $this->postJson('/api/reglements-fournisseurs', [
            'date_reglement' => '2026-05-10',
            'facture_id' => $facture->id,
            'montant' => 50000,
            'mode_paiement' => 'virement',
        ])->assertStatus(403);
    }

    public function test_creation_enregistre_le_paiement_sur_la_facture(): void
    {
        $this->actingAsWithPermissions(['reglements-fournisseurs.creer']);
        $facture = $this->facture();

        $this->postJson('/api/reglements-fournisseurs', [
            'date_reglement' => '2026-05-10',
            'facture_id' => $facture->id,
            'montant' => 40000,
            'mode_paiement' => 'virement',
        ])->assertSuccessful()->assertJson(['success' => true]);

        $facture->refresh();
        $this->assertEquals(40000, $facture->montant_paye);
        $this->assertSame(FactureFournisseur::STATUT_PARTIELLEMENT_PAYEE, $facture->statut);
    }

    public function test_montant_superieur_au_reste_a_payer_est_rejete(): void
    {
        $this->actingAsWithPermissions(['reglements-fournisseurs.creer']);
        $facture = $this->facture();

        $this->postJson('/api/reglements-fournisseurs', [
            'date_reglement' => '2026-05-10',
            'facture_id' => $facture->id,
            'montant' => 150000, // > 100000
            'mode_paiement' => 'virement',
        ])->assertStatus(422)->assertJson(['success' => false]);

        $this->assertEquals(0, $facture->fresh()->montant_paye);
    }

    public function test_mode_paiement_invalide_rejete(): void
    {
        $this->actingAsWithPermissions(['reglements-fournisseurs.creer']);
        $facture = $this->facture();

        $this->postJson('/api/reglements-fournisseurs', [
            'date_reglement' => '2026-05-10',
            'facture_id' => $facture->id,
            'montant' => 10000,
            'mode_paiement' => 'bitcoin',
        ])->assertStatus(422);
    }
}
