<?php

namespace Tests\Feature;

use Database\Factories\ApprovisionnementBanqueFactory;
use Database\Factories\FactureClientFactory;
use Database\Factories\FactureFournisseurFactory;
use Database\Factories\FournisseurFactory;
use Database\Factories\ReglementClientFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
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

    public function test_declaration_tva_par_mois_ne_retient_que_le_mois_demande(): void
    {
        $this->actingAsWithPermissions(['rapports-fournisseurs.voir']);
        $fournisseur = FournisseurFactory::new()->create();
        FactureFournisseurFactory::new()->create([
            'fournisseur_id' => $fournisseur->id,
            'numero_piece' => 'PC/TVA/JUIL',
            'date' => '2026-07-15',
            'assujetti_tva' => true,
        ]);
        FactureFournisseurFactory::new()->create([
            'fournisseur_id' => $fournisseur->id,
            'numero_piece' => 'PC/TVA/AOUT',
            'date' => '2026-08-03',
            'assujetti_tva' => true,
        ]);

        $this->getJson('/rapports/fournisseurs/api/declaration-tva?mode=mois_annee&mois=7&annee=2026')
            ->assertSuccessful()
            ->assertJsonPath('titreDeclaration', 'DÉCLARATION TVA MOIS DE JUILLET 2026')
            ->assertJsonCount(1, 'lignes')
            ->assertJsonPath('lignes.0.numero_piece', 'PC/TVA/JUIL')
            ->assertJsonPath('lignes.0.fournisseur_ifu', $fournisseur->ifu);
    }

    public function test_declaration_tva_reste_compatible_avec_les_dates_seules(): void
    {
        // Anciens liens / exports enregistrés : pas de `mode`, seulement les deux dates.
        $this->actingAsWithPermissions(['rapports-fournisseurs.voir']);
        FactureFournisseurFactory::new()->create(['date' => '2026-07-15', 'assujetti_tva' => true]);

        $this->getJson('/rapports/fournisseurs/api/declaration-tva?date_debut=2026-07-01&date_fin=2026-07-31')
            ->assertSuccessful()
            ->assertJsonPath('mode', 'periode')
            ->assertJsonCount(1, 'lignes');
    }

    public function test_export_excel_declaration_tva_contient_ifu_et_fournisseur(): void
    {
        $this->actingAsWithPermissions(['rapports-fournisseurs.voir']);
        $fournisseur = FournisseurFactory::new()->create(['nom' => 'SOCIÉTÉ TEST']);
        FactureFournisseurFactory::new()->create([
            'fournisseur_id' => $fournisseur->id,
            'date' => '2026-07-15',
            'assujetti_tva' => true,
        ]);

        $reponse = $this->get('/rapports/fournisseurs/excel/declaration-tva?mode=mois_annee&mois=7&annee=2026');
        $reponse->assertSuccessful();

        $chemin = tempnam(sys_get_temp_dir(), 'tva').'.xlsx';
        file_put_contents($chemin, $reponse->streamedContent());
        $feuille = IOFactory::load($chemin)->getActiveSheet();

        // Ligne 1 : titre ; ligne 3 : en-têtes ; ligne 4 : première facture.
        $entetes = $feuille->rangeToArray('A3:I3')[0];
        $this->assertContains('N° IFU', $entetes);
        $this->assertContains('Raison sociale', $entetes);
        $this->assertSame($fournisseur->ifu, (string) $feuille->getCell('C4')->getValue());
        $this->assertSame('SOCIÉTÉ TEST', $feuille->getCell('D4')->getValue());

        unlink($chemin);
    }
}
