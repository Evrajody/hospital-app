<?php

namespace Tests\Feature;

use Database\Factories\ApprovisionnementBanqueFactory;
use Database\Factories\FactureClientFactory;
use Database\Factories\ReglementClientFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsPermissions;
use Tests\TestCase;

class RapportBrouillardTest extends TestCase
{
    use RefreshDatabase;
    use SeedsPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();
    }

    /** Un bordereau SANS chèque ne doit PAS apparaître dans le brouillard de chèques. */
    public function test_bordereau_sans_cheque_est_exclu_du_brouillard(): void
    {
        $this->actingAsWithPermissions(['rapports-clients.voir']);

        // Bordereau AVEC un chèque → apparaît
        $approAvec = ApprovisionnementBanqueFactory::new()->create([
            'reference_bordereau' => 'BORD-AVEC', 'date_depot' => '2026-03-01', 'montant' => 50000,
        ]);
        $facture = FactureClientFactory::new()->create();
        ReglementClientFactory::new()->pourFacture($facture)->create([
            'approvisionnement_id' => $approAvec->id,
            'reference_cheque' => '111111',
            'date_reglement' => '2026-03-01',
            'montant' => 50000,
        ]);

        // Bordereau SANS chèque (aucun règlement rattaché) → exclu
        ApprovisionnementBanqueFactory::new()->create([
            'reference_bordereau' => 'BORD-SANS', 'date_depot' => '2026-03-05', 'montant' => 30000,
        ]);

        $libelles = collect(
            $this->getJson('/rapports/clients/api/brouillard-cheques?date_debut=2026-01-01&date_fin=2026-12-31')
                ->assertOk()->json('data')
        )->pluck('libelle')->all();

        $this->assertTrue(collect($libelles)->contains(fn ($l) => str_contains($l, 'BORD-AVEC')), 'Le bordereau avec chèque doit apparaître');
        $this->assertFalse(collect($libelles)->contains(fn ($l) => str_contains($l, 'BORD-SANS')), 'Le bordereau SANS chèque ne doit PAS apparaître');
    }

    /** Idem côté imputations comptables : pas de bordereau sans chèque. */
    public function test_bordereau_sans_cheque_est_exclu_des_imputations(): void
    {
        $this->actingAsWithPermissions(['rapports-clients.voir']);

        $approAvec = ApprovisionnementBanqueFactory::new()->create([
            'reference_bordereau' => 'IMP-AVEC', 'date_depot' => '2026-03-01', 'montant' => 50000,
        ]);
        $facture = FactureClientFactory::new()->create();
        ReglementClientFactory::new()->pourFacture($facture)->create([
            'approvisionnement_id' => $approAvec->id, 'reference_cheque' => '222', 'date_reglement' => '2026-03-01', 'montant' => 50000,
        ]);
        ApprovisionnementBanqueFactory::new()->create([
            'reference_bordereau' => 'IMP-SANS', 'date_depot' => '2026-03-05', 'montant' => 30000,
        ]);

        $refs = collect(
            $this->getJson('/rapports/clients/api/imputations-comptables?date_debut=2026-01-01&date_fin=2026-12-31')
                ->assertOk()->json('groupes')
        )->pluck('reference_bordereau')->all();

        $this->assertContains('IMP-AVEC', $refs);
        $this->assertNotContains('IMP-SANS', $refs);
    }

    /** Le PDF stream du brouillard se génère sans 500 (mémoire/temps relevés). */
    public function test_brouillard_pdf_stream_ok(): void
    {
        $this->actingAsWithPermissions(['rapports-clients.voir']);
        $appro = ApprovisionnementBanqueFactory::new()->create([
            'reference_bordereau' => 'BORD-1', 'date_depot' => '2026-03-01', 'montant' => 40000,
        ]);
        $facture = FactureClientFactory::new()->create();
        ReglementClientFactory::new()->pourFacture($facture)->create([
            'approvisionnement_id' => $appro->id, 'reference_cheque' => '222', 'date_reglement' => '2026-03-01',
        ]);

        $res = $this->get('/rapports/clients/pdf/brouillard-cheques?date_debut=2026-01-01&date_fin=2026-12-31&action=stream');
        $res->assertOk()->assertHeader('content-type', 'application/pdf');
    }
}
