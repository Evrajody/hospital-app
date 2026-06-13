<?php

namespace Tests\Feature;

use App\Models\FactureFournisseur;
use Database\Factories\FactureFournisseurFactory;
use Database\Factories\FournisseurFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsPermissions;
use Tests\TestCase;

class FactureFournisseurApiTest extends TestCase
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
            'date' => '2026-05-01',
            'fournisseur_id' => FournisseurFactory::new()->create()->id,
            'libelle' => 'Achat de médicaments',
            'montant_facture' => 100000,
            'assujetti_tva' => false,
        ], $overrides);
    }

    public function test_creation_refusee_sans_permission(): void
    {
        $this->actingAsWithPermissions(['factures-fournisseurs.voir']);
        $this->postJson('/api/factures-fournisseurs', $this->payload())->assertStatus(403);
    }

    public function test_creation_genere_le_numero_de_piece_et_le_statut_validee(): void
    {
        $this->actingAsWithPermissions(['factures-fournisseurs.creer']);

        $response = $this->postJson('/api/factures-fournisseurs', $this->payload())
            ->assertCreated()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('factures_fournisseurs', [
            'libelle' => 'Achat de médicaments',
            'statut' => FactureFournisseur::STATUT_VALIDEE,
        ]);
        $this->assertNotEmpty($response->json('data.numero_piece'));
    }

    public function test_validation_champs_obligatoires(): void
    {
        $this->actingAsWithPermissions(['factures-fournisseurs.creer']);

        $this->postJson('/api/factures-fournisseurs', ['montant_facture' => 1000])
            ->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_annuler_une_facture(): void
    {
        $this->actingAsWithPermissions(['factures-fournisseurs.modifier']);
        $facture = FactureFournisseurFactory::new()->create(['statut' => FactureFournisseur::STATUT_VALIDEE]);

        $this->postJson("/api/factures-fournisseurs/{$facture->id}/annuler")->assertSuccessful();

        $this->assertSame(FactureFournisseur::STATUT_ANNULEE, $facture->fresh()->statut);
    }

    public function test_suppression(): void
    {
        $this->actingAsWithPermissions(['factures-fournisseurs.supprimer']);
        $facture = FactureFournisseurFactory::new()->create();

        $this->deleteJson("/api/factures-fournisseurs/{$facture->id}")->assertSuccessful();
        $this->assertSoftDeleted('factures_fournisseurs', ['id' => $facture->id]);
    }

    /** Le sélecteur "Nouveau règlement" interroge ce filtre en recherche serveur. */
    public function test_filtre_non_payee_exclut_les_factures_payees(): void
    {
        $this->actingAsWithPermissions(['factures-fournisseurs.voir']);
        FactureFournisseurFactory::new()->create(['statut' => FactureFournisseur::STATUT_VALIDEE, 'libelle' => 'NONPAYEE A']);
        FactureFournisseurFactory::new()->create(['statut' => FactureFournisseur::STATUT_PARTIELLEMENT_PAYEE, 'libelle' => 'NONPAYEE B']);
        FactureFournisseurFactory::new()->payee()->create(['libelle' => 'PAYEE C']);

        $libelles = collect($this->getJson('/api/factures-fournisseurs?non_payee=1')->assertOk()->json('data'))
            ->pluck('libelle')->all();

        $this->assertContains('NONPAYEE A', $libelles);
        $this->assertContains('NONPAYEE B', $libelles);
        $this->assertNotContains('PAYEE C', $libelles);
    }

    public function test_filtre_non_payee_avec_recherche(): void
    {
        $this->actingAsWithPermissions(['factures-fournisseurs.voir']);
        FactureFournisseurFactory::new()->create(['statut' => FactureFournisseur::STATUT_VALIDEE, 'libelle' => 'Médicaments urgents']);
        FactureFournisseurFactory::new()->create(['statut' => FactureFournisseur::STATUT_VALIDEE, 'libelle' => 'Carburant groupe']);

        $libelles = collect($this->getJson('/api/factures-fournisseurs?non_payee=1&search=urgents')->assertOk()->json('data'))
            ->pluck('libelle')->all();

        $this->assertContains('Médicaments urgents', $libelles);
        $this->assertNotContains('Carburant groupe', $libelles);
    }
}
