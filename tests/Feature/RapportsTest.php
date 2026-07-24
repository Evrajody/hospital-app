<?php

namespace Tests\Feature;

use Database\Factories\ApprovisionnementBanqueFactory;
use Database\Factories\FactureClientFactory;
use Database\Factories\ReglementClientFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsPermissions;
use Tests\TestCase;

class RapportsTest extends TestCase
{
    use RefreshDatabase;
    use SeedsPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();
    }

    public function test_rapports_clients_refuses_sans_permission(): void
    {
        $this->actingAsWithPermissions(['rapports-fournisseurs.voir']);
        $this->getJson('/rapports/clients/api/etat-reglements?date_debut=2026-01-01&date_fin=2026-12-31')
            ->assertStatus(403);
    }

    public function test_etat_reglements_clients_ok_avec_permission(): void
    {
        $this->actingAsWithPermissions(['rapports-clients.voir']);
        $facture = FactureClientFactory::new()->create();
        ReglementClientFactory::new()->pourFacture($facture)->create();

        $this->getJson('/rapports/clients/api/etat-reglements?date_debut=2026-01-01&date_fin=2026-12-31')
            ->assertSuccessful();
    }

    public function test_etat_creances_exclut_une_facture_soldee_manuellement_a_la_date_d_arrete(): void
    {
        $this->actingAsWithPermissions(['rapports-clients.voir']);
        $facture = FactureClientFactory::new()->create([
            'reference' => 'SOLDEE/03/26',
            'date_facture' => '2026-03-01',
            'montant' => 100000,
            'montant_paye' => 40000,
            'statut' => \App\Models\FactureClient::STATUT_PAYEE,
            'date_solde' => '2026-03-20',
        ]);
        ReglementClientFactory::new()->pourFacture($facture)->create([
            'date_reglement' => '2026-03-10',
            'montant' => 40000,
        ]);

        $this->getJson('/rapports/clients/api/etat-creances?mode=un_client&client_id='.$facture->client_id.'&date_fin=2026-03-31')
            ->assertSuccessful()
            ->assertJsonMissing(['reference' => 'SOLDEE/03/26']);
    }

    public function test_etat_creances_inclut_une_facture_soldee_apres_la_date_d_arrete(): void
    {
        $this->actingAsWithPermissions(['rapports-clients.voir']);
        $facture = FactureClientFactory::new()->create([
            'reference' => 'APRES/03/26',
            'date_facture' => '2026-03-01',
            'montant' => 100000,
            'montant_paye' => 40000,
            'statut' => \App\Models\FactureClient::STATUT_PAYEE,
            'date_solde' => '2026-04-05',
        ]);
        ReglementClientFactory::new()->pourFacture($facture)->create([
            'date_reglement' => '2026-03-10',
            'montant' => 40000,
        ]);

        $this->getJson('/rapports/clients/api/etat-creances?mode=un_client&client_id='.$facture->client_id.'&date_fin=2026-03-31')
            ->assertSuccessful()
            ->assertJsonFragment(['reference' => 'APRES/03/26']);
    }

    public function test_etat_creances_sans_date_exclut_toute_facture_payee(): void
    {
        $this->actingAsWithPermissions(['rapports-clients.voir']);
        $facture = FactureClientFactory::new()->create([
            'reference' => 'PAYEE/03/26',
            'statut' => \App\Models\FactureClient::STATUT_PAYEE,
            'date_solde' => '2026-03-20',
        ]);

        $this->getJson('/rapports/clients/api/etat-creances?mode=un_client&client_id='.$facture->client_id)
            ->assertSuccessful()
            ->assertJsonMissing(['reference' => 'PAYEE/03/26']);
    }

    public function test_etat_reglements_conserve_le_solde_negatif_d_un_trop_percu(): void
    {
        $this->actingAsWithPermissions(['rapports-clients.voir']);
        $facture = FactureClientFactory::new()->create([
            'reference' => 'TROP/03/26',
            'date_facture' => '2026-03-01',
            'montant' => 100000,
            'montant_paye' => 120000,
            'statut' => \App\Models\FactureClient::STATUT_PAYEE,
            'date_solde' => '2026-03-15',
        ]);
        ReglementClientFactory::new()->pourFacture($facture)->create([
            'date_reglement' => '2026-03-15',
            'montant' => 120000,
        ]);

        $this->getJson('/rapports/clients/api/etat-reglements?mode=un_client&client_id='.$facture->client_id)
            ->assertSuccessful()
            ->assertJsonFragment([
                'reference' => 'TROP/03/26',
                'solde' => -20000,
            ]);
    }

    public function test_brouillard_cheques_ok_avec_donnees(): void
    {
        $this->actingAsWithPermissions(['rapports-clients.voir']);

        $appro = ApprovisionnementBanqueFactory::new()->create([
            'date_depot' => '2026-03-01',
            'reference_bordereau' => 'BORD-1',
        ]);
        $facture = FactureClientFactory::new()->create();
        ReglementClientFactory::new()->pourFacture($facture)->create([
            'approvisionnement_id' => $appro->id,
            'reference_cheque' => '123456',
            'date_reglement' => '2026-03-01',
        ]);

        $this->getJson('/rapports/clients/api/brouillard-cheques?date_debut=2026-01-01&date_fin=2026-12-31')
            ->assertSuccessful()
            ->assertJsonStructure(['data']);
    }

    public function test_pertes_rejets_ok(): void
    {
        $this->actingAsWithPermissions(['rapports-clients.voir']);

        $this->getJson('/rapports/clients/api/pertes-rejets?date_debut=2026-01-01&date_fin=2026-12-31')
            ->assertSuccessful();
    }

    public function test_rapports_fournisseurs_refuses_sans_permission(): void
    {
        $this->actingAsWithPermissions(['rapports-clients.voir']);
        $this->getJson('/rapports/fournisseurs/api/situation-fournisseurs')
            ->assertStatus(403);
    }

    public function test_situation_fournisseurs_ok_avec_permission(): void
    {
        $this->actingAsWithPermissions(['rapports-fournisseurs.voir']);
        $this->getJson('/rapports/fournisseurs/api/situation-fournisseurs?point_au=2026-12-31')
            ->assertSuccessful();
    }
}
