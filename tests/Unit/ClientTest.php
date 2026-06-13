<?php

namespace Tests\Unit;

use App\Models\Client;
use Database\Factories\ClientFactory;
use Database\Factories\CompteComptableFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_to_api_array_libelle_du_type(): void
    {
        $client = ClientFactory::new()->create(['type_client' => 'societe']);
        $this->assertSame('Société', $client->toApiArray()['type_client_label']);

        $autre = ClientFactory::new()->create(['type_client' => 'personnel']);
        $this->assertSame('Personnel', $autre->toApiArray()['type_client_label']);
    }

    public function test_recherche_ilike_sur_nom(): void
    {
        ClientFactory::new()->create(['nom' => 'Société Générale']);
        ClientFactory::new()->create(['nom' => 'Entreprise Locale']);

        $resultats = Client::recherche('générale')->get();

        $this->assertCount(1, $resultats);
    }

    public function test_a_compte_comptable(): void
    {
        $compte = CompteComptableFactory::new()->numero('4111')->create();
        $client = ClientFactory::new()->create(['compte_comptable_id' => $compte->id]);

        $this->assertTrue($client->aCompteComptable());
        $this->assertSame('4111', $client->getNumeroCompte());

        $sans = ClientFactory::new()->create(['compte_comptable_id' => null]);
        $this->assertFalse($sans->aCompteComptable());
    }

    public function test_relations_factures_et_reglements(): void
    {
        $client = ClientFactory::new()->create();
        \Database\Factories\FactureClientFactory::new()->create(['client_id' => $client->id]);

        $this->assertCount(1, $client->facturesClient);
    }
}
