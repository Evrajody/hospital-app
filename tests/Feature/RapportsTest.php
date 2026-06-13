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
