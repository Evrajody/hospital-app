<?php

namespace Tests\Feature;

use App\Models\FactureClient;
use App\Models\FactureFournisseur;
use Database\Factories\ClientFactory;
use Database\Factories\CompteComptableFactory;
use Database\Factories\FactureClientFactory;
use Database\Factories\FactureFournisseurFactory;
use Database\Factories\FournisseurFactory;
use Database\Factories\ReglementClientFactory;
use Database\Factories\ReglementFournisseurFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsPermissions;
use Tests\TestCase;

/**
 * Verrouille les MONTANTS exacts des rapports avant/après l'optimisation des N+1,
 * pour garantir que le refactor (eager-loading) ne change AUCUN chiffre.
 */
class RapportN1RegressionTest extends TestCase
{
    use RefreshDatabase;
    use SeedsPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();
    }

    /** Jeu de données déterministe partagé par les assertions. */
    private function seedSituation(): array
    {
        $cptA = CompteComptableFactory::new()->numero('40111')->create(['libelle' => 'Frs A']);
        $cptB = CompteComptableFactory::new()->numero('40112')->create(['libelle' => 'Frs B']);

        $fA = FournisseurFactory::new()->create(['nom' => 'FOURNISSEUR A', 'compte_comptable_id' => $cptA->id]);
        $fB = FournisseurFactory::new()->create(['nom' => 'FOURNISSEUR B', 'compte_comptable_id' => $cptB->id]);

        // A : facture 100 000 réglée à 50 000 → restant dû 50 000 (apparaît)
        $factA = FactureFournisseurFactory::new()->create([
            'fournisseur_id' => $fA->id, 'montant_facture' => 100000, 'assujetti_tva' => false,
            'date' => '2026-03-01', 'numero_piece' => 'PC/A/0001', 'statut' => FactureFournisseur::STATUT_VALIDEE,
        ]);
        ReglementFournisseurFactory::new()->pourFacture($factA)->create([
            'montant' => 50000, 'date_reglement' => '2026-03-10',
        ]);

        // B : facture 60 000 réglée à 60 000 → soldée → n'apparaît PAS
        $factB = FactureFournisseurFactory::new()->create([
            'fournisseur_id' => $fB->id, 'montant_facture' => 60000, 'assujetti_tva' => false,
            'date' => '2026-04-01', 'numero_piece' => 'PC/B/0001', 'statut' => FactureFournisseur::STATUT_VALIDEE,
        ]);
        ReglementFournisseurFactory::new()->pourFacture($factB)->create([
            'montant' => 60000, 'date_reglement' => '2026-04-10',
        ]);

        return compact('fA', 'fB');
    }

    public function test_situation_fournisseurs_mode_tous_montants_exacts(): void
    {
        $this->actingAsWithPermissions(['rapports-fournisseurs.voir']);
        $this->seedSituation();

        $json = $this->getJson('/rapports/fournisseurs/api/situation-fournisseurs?mode=tous&date_debut=2026-01-01&date_fin=2026-12-31')
            ->assertOk()->json();

        // Seul le fournisseur A apparaît (B est soldé)
        $this->assertCount(1, $json['data']);
        $this->assertSame('FOURNISSEUR A', $json['data'][0]['raison_sociale']);
        $this->assertEqualsWithDelta(100000, $json['data'][0]['montant_du'], 0.01);
        $this->assertEqualsWithDelta(50000, $json['data'][0]['montant_reglements'], 0.01);
        $this->assertEqualsWithDelta(50000, $json['data'][0]['restant_du'], 0.01);

        $this->assertEqualsWithDelta(100000, $json['grandTotal']['montant_du'], 0.01);
        $this->assertEqualsWithDelta(50000, $json['grandTotal']['montant_reglements'], 0.01);
        $this->assertEqualsWithDelta(50000, $json['grandTotal']['restant_du'], 0.01);
    }

    public function test_situation_fournisseurs_mode_par_fournisseur_montants_exacts(): void
    {
        $this->actingAsWithPermissions(['rapports-fournisseurs.voir']);
        ['fA' => $fA] = $this->seedSituation();

        $json = $this->getJson("/rapports/fournisseurs/api/situation-fournisseurs?mode=par_fournisseur&fournisseur_id={$fA->id}&date_debut=2026-01-01&date_fin=2026-12-31")
            ->assertOk()->json();

        $this->assertCount(1, $json['data']);
        $this->assertStringContainsString('FOURNISSEUR A', $json['data'][0]['fournisseur']);
        $this->assertCount(1, $json['data'][0]['lignes']);
        $ligne = $json['data'][0]['lignes'][0];
        $this->assertSame('PC/A/0001', $ligne['numero_piece']);
        $this->assertEqualsWithDelta(100000, $ligne['montant_du'], 0.01);
        $this->assertEqualsWithDelta(50000, $ligne['total_reglement'], 0.01);
        $this->assertEqualsWithDelta(50000, $ligne['solde'], 0.01);

        $this->assertEqualsWithDelta(100000, $json['data'][0]['totaux']['montant_du'], 0.01);
        $this->assertEqualsWithDelta(50000, $json['grandTotaux']['total_reglement'], 0.01);
        $this->assertEqualsWithDelta(50000, $json['grandTotaux']['solde'], 0.01);
    }

    public function test_etat_reglements_clients_mode_tous_clients_montants_exacts(): void
    {
        $this->actingAsWithPermissions(['rapports-clients.voir']);

        $client = ClientFactory::new()->create(['nom' => 'CLIENT X', 'type_client' => 'societe']);
        $facture = FactureClientFactory::new()->create([
            'client_id' => $client->id, 'montant' => 100000, 'ristourne' => 0, 'date_facture' => '2026-05-01',
        ]);
        ReglementClientFactory::new()->pourFacture($facture)->create([
            'montant' => 40000, 'date_reglement' => '2026-05-10', 'montant_rejet' => 0,
        ]);
        $facture->update(['statut' => FactureClient::STATUT_PAYEE, 'date_solde' => '2026-05-15']);

        $json = $this->getJson('/rapports/clients/api/etat-reglements?mode=tous_clients&date_debut=2026-01-01&date_fin=2026-12-31')
            ->assertOk()->json();

        $this->assertCount(1, $json['data']);
        $row = $json['data'][0];
        $this->assertSame('CLIENT X', $row['raison_sociale']);
        $this->assertEqualsWithDelta(100000, $row['total_facture'], 0.01);
        $this->assertEqualsWithDelta(40000, $row['total_paye'], 0.01);
        $this->assertEqualsWithDelta(0, $row['total_perte'], 0.01);
        $this->assertEqualsWithDelta(0, $row['total_rejet'], 0.01);
        $this->assertEqualsWithDelta(60000, $row['total_solde'], 0.01);
    }
}
