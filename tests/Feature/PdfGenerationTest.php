<?php

namespace Tests\Feature;

use App\Models\FactureClient;
use Database\Factories\ApprovisionnementBanqueFactory;
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

class PdfGenerationTest extends TestCase
{
    use RefreshDatabase;
    use SeedsPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();
    }

    /** Le bordereau de transmission fournisseurs doit produire un vrai PDF. */
    public function test_bordereau_transmission_genere_un_pdf_valide(): void
    {
        $this->actingAsWithPermissions(['rapports-fournisseurs.voir']);

        $compte = CompteComptableFactory::new()->numero('4011')->create();
        $fournisseur = FournisseurFactory::new()->create(['compte_comptable_id' => $compte->id]);
        $facture = FactureFournisseurFactory::new()->create(['fournisseur_id' => $fournisseur->id]);
        $reglement = ReglementFournisseurFactory::new()->pourFacture($facture)->create();

        $response = $this->get("/rapports/fournisseurs/pdf/bordereau-transmission?ids={$reglement->id}");

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    /**
     * Conformité : les informations du bordereau de transmission rendues dans le PDF
     * (nom fournisseur, n° pièce, montant formaté) correspondent bien aux données.
     */
    public function test_conformite_des_informations_du_bordereau_transmission(): void
    {
        $compte = CompteComptableFactory::new()->numero('40115')->create();
        $fournisseur = FournisseurFactory::new()->create([
            'nom' => 'PHARMA PLUS SARL',
            'compte_comptable_id' => $compte->id,
        ]);
        $facture = FactureFournisseurFactory::new()->create([
            'fournisseur_id' => $fournisseur->id,
            'numero_piece' => 'PC/026/0042',
        ]);

        $data = [
            'titre' => 'BORDEREAU DE TRANSMISSION',
            'generatedAt' => '01/06/2026 à 10:00',
            'generatedBy' => 'Testeur',
            'lignes' => [[
                'date' => '10/05/2026',
                'fournisseur' => "[40115] {$fournisseur->nom}",
                'numero_piece' => $facture->numero_piece,
                'mode_paiement' => 'Virement',
                'institution' => 'BOA',
                'montant' => 1234567,
                'beneficiaire' => 'PHARMA PLUS SARL',
            ]],
        ];

        $html = view('pdf.rapports-fournisseurs.bordereau-transmission', $data)->render();

        $this->assertStringContainsString('PHARMA PLUS SARL', $html);
        $this->assertStringContainsString('PC/026/0042', $html);
        $this->assertStringContainsString('[40115]', $html);
        // Montant formaté à la française (séparateur d'espace, 0 décimale)
        $this->assertStringContainsString('1 234 567', $html);
    }

    /** Le brouillard de chèques clients doit produire un vrai PDF. */
    public function test_brouillard_cheques_genere_un_pdf_valide(): void
    {
        $this->actingAsWithPermissions(['rapports-clients.voir']);

        $appro = ApprovisionnementBanqueFactory::new()->create([
            'date_depot' => '2026-03-01',
            'reference_bordereau' => 'BORD-2026-1',
        ]);
        $facture = FactureClientFactory::new()->create();
        ReglementClientFactory::new()->pourFacture($facture)->create([
            'approvisionnement_id' => $appro->id,
            'reference_cheque' => '654321',
            'date_reglement' => '2026-03-01',
        ]);

        $response = $this->get('/rapports/clients/pdf/brouillard-cheques?date_debut=2026-01-01&date_fin=2026-12-31');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    /** Le PDF généré en mode "stream" (impression) est aussi un PDF valide. */
    public function test_brouillard_cheques_stream_est_un_pdf(): void
    {
        $this->actingAsWithPermissions(['rapports-clients.voir']);

        $response = $this->get('/rapports/clients/pdf/brouillard-cheques?date_debut=2026-01-01&date_fin=2026-12-31&action=stream');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    // ==========================================================================
    // Validité de rendu PDF (bout-en-bout) pour les autres états
    // ==========================================================================

    private function assertPdf($response): void
    {
        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_etat_avances_affiche_les_beneficiaires_dans_entete_et_non_dans_tableau(): void
    {
        $data = [
            'titre' => 'ÉTAT DES AVANCES CLIENTS',
            'periode' => ['debut' => null, 'fin' => null],
            'generatedAt' => '10/08/2026 à 10:00',
            'generatedBy' => 'Testeur',
            'avances' => [[
                'emetteur_compte' => '4111.001',
                'emetteur_nom' => 'ASSURANCE TEST',
                'beneficiaires' => ['PATIENT ALPHA', 'PATIENT BETA'],
                'numero_cheque' => 'CHQ-001',
                'date_cheque' => '10/08/2026',
                'montant' => 100000,
                'montant_utilise' => 25000,
                'montant_restant' => 75000,
                'rows' => [[
                    'facture_ref' => 'FAC-001',
                    'date_facture' => '09/08/2026',
                    'montant_facture' => 50000,
                    'montant_regle' => 25000,
                ]],
            ]],
        ];

        $html = view('pdf.rapports-clients.etat-avances', $data)->render();

        $this->assertStringContainsString('PATIENT ALPHA • PATIENT BETA', $html);
        $this->assertStringNotContainsString('<th>Bénéficiaire</th>', $html);
        $this->assertStringContainsString('FAC-001', $html);
    }

    public function test_etat_reglements_clients_pdf_valide(): void
    {
        $this->actingAsWithPermissions(['rapports-clients.voir']);
        $client = ClientFactory::new()->create();
        $facture = FactureClientFactory::new()->create(['client_id' => $client->id, 'date_facture' => '2026-04-01']);
        $facture->update(['statut' => FactureClient::STATUT_PAYEE, 'date_solde' => '2026-04-15']);

        $this->assertPdf($this->get('/rapports/clients/pdf/etat-reglements?mode=un_client&client_id='.$client->id.'&date_debut=2026-01-01&date_fin=2026-12-31'));
    }

    public function test_etat_creances_clients_pdf_valide(): void
    {
        $this->actingAsWithPermissions(['rapports-clients.voir']);
        $client = ClientFactory::new()->create();
        FactureClientFactory::new()->create(['client_id' => $client->id, 'date_facture' => '2026-04-01']);

        $this->assertPdf($this->get('/rapports/clients/pdf/etat-creances?mode=un_client&client_id='.$client->id.'&date_debut=2026-01-01&date_fin=2026-12-31'));
    }

    public function test_situation_fournisseurs_pdf_valide(): void
    {
        $this->actingAsWithPermissions(['rapports-fournisseurs.voir']);
        $fournisseur = FournisseurFactory::new()->create();
        FactureFournisseurFactory::new()->create(['fournisseur_id' => $fournisseur->id]);

        $this->assertPdf($this->get('/rapports/fournisseurs/pdf/situation-fournisseurs?mode=par_fournisseur&fournisseur_id='.$fournisseur->id));
    }

    public function test_mouvement_factures_pdf_valide(): void
    {
        $this->actingAsWithPermissions(['rapports-fournisseurs.voir']);
        $fournisseur = FournisseurFactory::new()->create();
        FactureFournisseurFactory::new()->create(['fournisseur_id' => $fournisseur->id]);

        $this->assertPdf($this->get('/rapports/fournisseurs/pdf/mouvement-factures?fournisseur_id='.$fournisseur->id));
    }

    public function test_point_periodique_pdf_valide(): void
    {
        $this->actingAsWithPermissions(['rapports-fournisseurs.voir']);
        FactureFournisseurFactory::new()->create(['date' => '2026-05-01']);

        $this->assertPdf($this->get('/rapports/fournisseurs/pdf/point-periodique?date_debut=2026-01-01&date_fin=2026-12-31'));
    }

    public function test_declaration_aib_pdf_valide(): void
    {
        $this->actingAsWithPermissions(['rapports-fournisseurs.voir']);

        $this->assertPdf($this->get('/rapports/fournisseurs/pdf/declaration-aib?date_debut=2026-01-01&date_fin=2026-12-31'));
    }

    public function test_mandats_multiples_pdf_valide(): void
    {
        $this->actingAsWithPermissions(['rapports-fournisseurs.voir']);
        $facture = FactureFournisseurFactory::new()->create();
        $reglement = ReglementFournisseurFactory::new()->pourFacture($facture)->create();

        $this->assertPdf($this->get("/rapports/fournisseurs/pdf/mandats?ids={$reglement->id}"));
    }

    public function test_mandat_reglement_fournisseur_pdf_valide(): void
    {
        $this->actingAsWithPermissions(['reglements-fournisseurs.voir']);
        $facture = FactureFournisseurFactory::new()->create();
        $reglement = ReglementFournisseurFactory::new()->pourFacture($facture)->create();

        $this->assertPdf($this->get("/reglements-fournisseurs/{$reglement->id}/mandat"));
    }

    /**
     * Conformité : la situation des fournisseurs rendue dans le PDF affiche bien
     * la raison sociale et les montants attendus.
     */
    public function test_conformite_situation_fournisseurs(): void
    {
        $data = [
            'mode' => 'tous',
            'titre' => 'Situation des fournisseurs',
            'generatedAt' => '01/06/2026 à 10:00',
            'generatedBy' => 'Testeur',
            'data' => [[
                'numero' => 1,
                'numero_compte' => '40115',
                'raison_sociale' => 'GROSSISTE MEDICAL SA',
                'montant_du' => 2000000,
                'montant_reglements' => 750000,
                'restant_du' => 1250000,
            ]],
            'grandTotal' => [
                'montant_du' => 2000000,
                'montant_reglements' => 750000,
                'restant_du' => 1250000,
            ],
        ];

        $html = view('pdf.rapports-fournisseurs.situation-fournisseurs', $data)->render();

        $this->assertStringContainsString('GROSSISTE MEDICAL SA', $html);
        $this->assertStringContainsString('2 000 000', $html);
        $this->assertStringContainsString('1 250 000', $html);
    }
}
