<?php

namespace Tests\Feature;

use App\Models\FactureClient;
use Database\Factories\ClientFactory;
use Database\Factories\FactureClientFactory;
use Database\Factories\FournisseurFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\SeedsPermissions;
use Tests\TestCase;

class PerformanceImprovementsTest extends TestCase
{
    use RefreshDatabase;
    use SeedsPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();
    }

    public function test_endpoint_factures_clients_impayees_filtre_et_recherche(): void
    {
        $this->actingAsWithPermissions(['factures-clients.voir']);
        $client = ClientFactory::new()->create(['nom' => 'CLINIQUE DU LAC']);
        FactureClientFactory::new()->create(['client_id' => $client->id, 'reference' => 'IMP-001', 'montant' => 50000, 'statut' => FactureClient::STATUT_NON_PAYEE]);
        FactureClientFactory::new()->create(['reference' => 'PAYEE-XX', 'statut' => FactureClient::STATUT_PAYEE]);

        $refs = collect($this->getJson('/api/factures-clients/impayees')->assertOk()->json('data'))
            ->pluck('reference')->all();
        $this->assertContains('IMP-001', $refs);
        $this->assertNotContains('PAYEE-XX', $refs);

        // Recherche par nom de client
        $refs = collect($this->getJson('/api/factures-clients/impayees?search=lac')->assertOk()->json('data'))
            ->pluck('reference')->all();
        $this->assertContains('IMP-001', $refs);
    }

    public function test_page_reglements_clients_ne_precharge_plus_les_factures_impayees(): void
    {
        $this->actingAsWithPermissions(['reglements-clients.voir']);
        FactureClientFactory::new()->count(3)->create(['statut' => FactureClient::STATUT_NON_PAYEE]);

        $this->get('/reglements-clients')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('ReglementClients/Index')
                ->has('reglements')
                ->has('pagination')
                ->missing('facturesImpayees'));
    }

    public function test_reglements_clients_pagination_et_filtre_serveur(): void
    {
        $this->actingAsWithPermissions(['reglements-clients.voir']);
        // 25 règlements "reglement" + 1 "perte" → page de 20, filtre type côté serveur
        $f = FactureClientFactory::new()->create(['montant' => 1000000, 'ristourne' => 0]);
        \Database\Factories\ReglementClientFactory::new()->pourFacture($f)->count(25)->create(['montant' => 1000, 'type_reglement' => 'reglement']);
        \Database\Factories\ReglementClientFactory::new()->pourFacture($f)->perte()->create(['montant' => 500]);

        // Page 1 : 20 règlements max (server-paginated), total = 26
        $this->get('/reglements-clients')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('pagination.total', 26)
                ->where('pagination.per_page', 20)
                ->count('reglements', 20));

        // Filtre type_reglement=perte → 1 seul
        $this->get('/reglements-clients?type_reglement=perte')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('pagination.total', 1)
                ->count('reglements', 1));
    }

    public function test_avances_pagination_et_stats_sql(): void
    {
        $this->actingAsWithPermissions(['reglements-clients.voir']);
        \Database\Factories\AvanceClientFactory::new()->count(22)->create(['montant' => 100000, 'montant_utilise' => 40000]);

        $this->get('/avances-clients')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Avances/Index')
                ->where('pagination.total', 22)
                ->count('avances', 20)
                ->where('stats.total_avances', 2200000)
                ->where('stats.total_disponible', 1320000)); // 22 × (100000-40000)
    }

    public function test_stats_factures_clients_calculees_en_sql(): void
    {
        $this->actingAsWithPermissions(['factures-clients.voir']);
        FactureClientFactory::new()->create(['montant' => 100000, 'montant_paye' => 30000]);
        FactureClientFactory::new()->create(['montant' => 50000, 'montant_paye' => 50000]);

        $this->get('/factures-clients')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('stats.total_facture', 150000)
                ->where('stats.total_paye', 80000));
    }

    public function test_liste_clients_triee_du_plus_recent_au_plus_ancien(): void
    {
        $this->actingAsWithPermissions(['clients.voir']);
        $ancien = ClientFactory::new()->create(['nom' => 'ANCIEN']);
        // forcer un created_at antérieur
        $ancien->forceFill(['created_at' => now()->subYear()])->saveQuietly();
        ClientFactory::new()->create(['nom' => 'RECENT']);

        $this->get('/clients')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('clients.0.nom', 'RECENT'));
    }

    public function test_liste_fournisseurs_triee_du_plus_recent_au_plus_ancien(): void
    {
        $this->actingAsWithPermissions(['fournisseurs.voir']);
        $ancien = FournisseurFactory::new()->create(['nom' => 'ANCIEN FRS']);
        $ancien->forceFill(['created_at' => now()->subYear()])->saveQuietly();
        FournisseurFactory::new()->create(['nom' => 'RECENT FRS']);

        $this->get('/fournisseurs')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('fournisseurs.0.nom', 'RECENT FRS'));
    }
}
