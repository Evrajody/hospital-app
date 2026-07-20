<?php

namespace Tests\Feature;

use App\Models\FactureClient;
use App\Models\FactureFournisseur;
use Database\Factories\ClientFactory;
use Database\Factories\FactureClientFactory;
use Database\Factories\FactureFournisseurFactory;
use Database\Factories\FournisseurFactory;
use Database\Factories\ReglementClientFactory;
use Database\Factories\ReglementFournisseurFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsPermissions;
use Tests\TestCase;

/**
 * Tous les états sont attendus triés du plus récent au plus ancien.
 */
class RapportsTriTest extends TestCase
{
    use RefreshDatabase;
    use SeedsPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();
    }

    public function test_etat_reglements_clients_du_plus_recent_au_plus_ancien(): void
    {
        $this->actingAsWithPermissions(['rapports-clients.voir']);
        $client = ClientFactory::new()->create();
        FactureClientFactory::new()->create([
            'client_id' => $client->id, 'date_facture' => '2026-01-10', 'reference' => 'ANCIENNE',
            'statut' => FactureClient::STATUT_PAYEE, 'date_solde' => '2026-01-20',
        ]);
        FactureClientFactory::new()->create([
            'client_id' => $client->id, 'date_facture' => '2026-06-10', 'reference' => 'RECENTE',
            'statut' => FactureClient::STATUT_PAYEE, 'date_solde' => '2026-06-20',
        ]);

        $data = $this->getJson('/rapports/clients/api/etat-reglements?mode=un_client&client_id='.$client->id.'&date_debut=2026-01-01&date_fin=2026-12-31')
            ->assertOk()->json('data.0.lignes');

        $this->assertSame('RECENTE', $data[0]['reference']);
        $this->assertSame('ANCIENNE', $data[1]['reference']);
    }

    public function test_pertes_rejets_du_plus_recent_au_plus_ancien(): void
    {
        $this->actingAsWithPermissions(['rapports-clients.voir']);
        $f1 = FactureClientFactory::new()->create();
        $f2 = FactureClientFactory::new()->create();
        ReglementClientFactory::new()->pourFacture($f1)->perte()->create(['date_reglement' => '2026-02-01', 'montant' => 1000]);
        ReglementClientFactory::new()->pourFacture($f2)->perte()->create(['date_reglement' => '2026-09-01', 'montant' => 2000]);

        $lignes = $this->getJson('/rapports/clients/api/pertes-rejets?date_debut=2026-01-01&date_fin=2026-12-31')
            ->assertOk()->json('data') ?? [];

        // La première ligne doit être la plus récente (01/09/2026)
        $this->assertNotEmpty($lignes);
        $this->assertSame('01/09/2026', $lignes[0]['date_reglement']);
    }

    public function test_mouvement_factures_fournisseur_du_plus_recent_au_plus_ancien(): void
    {
        $this->actingAsWithPermissions(['rapports-fournisseurs.voir']);
        $fournisseur = FournisseurFactory::new()->create();
        FactureFournisseurFactory::new()->create(['fournisseur_id' => $fournisseur->id, 'date' => '2026-01-15', 'numero_piece' => 'PC/V/ANCIENNE']);
        FactureFournisseurFactory::new()->create(['fournisseur_id' => $fournisseur->id, 'date' => '2026-07-15', 'numero_piece' => 'PC/V/RECENTE']);

        $lignes = $this->getJson('/rapports/fournisseurs/api/mouvement-factures?fournisseur_id='.$fournisseur->id)
            ->assertOk()->json('lignes');

        $this->assertSame('PC/V/RECENTE', $lignes[0]['numero_piece']);
        $this->assertSame('PC/V/ANCIENNE', $lignes[1]['numero_piece']);
    }

    public function test_bordereau_transmission_fournisseurs_du_plus_recent_au_plus_ancien(): void
    {
        $this->actingAsWithPermissions(['rapports-fournisseurs.voir']);
        $facture = FactureFournisseurFactory::new()->create(['statut' => FactureFournisseur::STATUT_VALIDEE]);
        ReglementFournisseurFactory::new()->pourFacture($facture)->create(['date_reglement' => '2026-03-01', 'montant' => 1000, 'reference' => 'ANCIEN']);
        ReglementFournisseurFactory::new()->pourFacture($facture)->create(['date_reglement' => '2026-08-01', 'montant' => 2000, 'reference' => 'RECENT']);

        $reglements = $this->getJson('/rapports/fournisseurs/api/bordereau-transmission?date_debut=2026-01-01&date_fin=2026-12-31')
            ->assertOk()->json('reglements');

        $this->assertSame('01/08/2026', $reglements[0]['date_reglement_formatted']);
        $this->assertSame('01/03/2026', $reglements[1]['date_reglement_formatted']);
    }
}
