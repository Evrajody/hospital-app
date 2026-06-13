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

    public function test_la_page_reglements_ne_precharge_plus_les_factures_impayees(): void
    {
        // Régression : facturesImpayees gonflait les props Inertia → erreur Firefox
        // history.pushState (NS_ERROR_ILLEGAL_VALUE) sur gros volume. Désormais chargé
        // en recherche serveur à l'ouverture du formulaire.
        $this->actingAsWithPermissions(['reglements-fournisseurs.voir']);
        \Database\Factories\FactureFournisseurFactory::new()->count(3)->create();

        $this->get('/reglements-fournisseurs')
            ->assertOk()
            ->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page
                ->component('ReglementsFournisseurs/Index')
                ->has('reglements')
                ->missing('facturesImpayees'));
    }

    public function test_la_date_reglement_envoyee_est_stockee_telle_quelle(): void
    {
        $this->actingAsWithPermissions(['reglements-fournisseurs.creer']);
        $facture = $this->facture();

        $this->postJson('/api/reglements-fournisseurs', [
            'date_reglement' => '2026-05-10',
            'facture_id' => $facture->id,
            'montant' => 10000,
            'mode_paiement' => 'virement',
        ])->assertSuccessful();

        // Pas de décalage de jour côté serveur : la date stockée == la date envoyée.
        $this->assertSame('2026-05-10', \App\Models\ReglementFournisseur::latest('id')->first()->date_reglement->format('Y-m-d'));
    }

    public function test_modifier_la_date_reglement_ne_change_pas_la_date_reference(): void
    {
        $this->actingAsWithPermissions(['reglements-fournisseurs.modifier']);
        $facture = $this->facture();
        $reglement = \Database\Factories\ReglementFournisseurFactory::new()->pourFacture($facture)->create([
            'date_reglement' => '2026-05-10',
            'date_reference' => '2026-05-08',
            'montant' => 30000,
            'mode_paiement' => 'cheque',
            'reference' => 'CHQ-001',
        ]);

        $this->putJson("/api/reglements-fournisseurs/{$reglement->id}", [
            'facture_id' => $facture->id,
            'date_reglement' => '2026-06-15', // on ne change QUE la date de règlement
            'montant' => 30000,
            'mode_paiement' => 'cheque',
            'reference' => 'CHQ-001',
            'date_reference' => '2026-05-08',
        ])->assertSuccessful();

        $reglement->refresh();
        $this->assertSame('2026-06-15', $reglement->date_reglement->format('Y-m-d'));
        $this->assertSame('2026-05-08', $reglement->date_reference->format('Y-m-d'));
    }
}
