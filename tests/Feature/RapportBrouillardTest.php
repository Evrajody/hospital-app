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

    /** Un bordereau SANS chèque doit quand même être comptabilisé (ligne SB). */
    public function test_bordereau_sans_cheque_apparait_dans_le_brouillard(): void
    {
        $this->actingAsWithPermissions(['rapports-clients.voir']);

        // Bordereau AVEC un chèque
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

        // Bordereau SANS chèque (aucun règlement rattaché)
        $approSans = ApprovisionnementBanqueFactory::new()->create([
            'reference_bordereau' => 'BORD-SANS', 'date_depot' => '2026-03-05', 'montant' => 30000,
        ]);

        $data = $this->getJson('/rapports/clients/api/brouillard-cheques?date_debut=2026-01-01&date_fin=2026-12-31')
            ->assertOk()->json('data');

        $libelles = collect($data)->pluck('libelle')->all();
        // Les DEUX bordereaux ont leur ligne SB
        $this->assertTrue(collect($libelles)->contains(fn ($l) => str_contains($l, 'BORD-AVEC')), 'Bordereau avec chèque manquant');
        $this->assertTrue(collect($libelles)->contains(fn ($l) => str_contains($l, 'BORD-SANS')), 'Bordereau SANS chèque manquant');

        // Le bordereau sans chèque a une ligne SB (crédit = montant) et aucune ligne CH
        $sbSans = collect($data)->first(fn ($r) => $r['nature'] === 'SB' && str_contains($r['libelle'], 'BORD-SANS'));
        $this->assertNotNull($sbSans);
        $this->assertEquals(30000, $sbSans['credit']);
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
