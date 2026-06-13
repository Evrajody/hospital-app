<?php

namespace Tests\Unit;

use App\Models\ReglementClient;
use Database\Factories\ReglementClientFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReglementClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_accesseurs_type_reglement(): void
    {
        $reglement = ReglementClientFactory::new()->create(['type_reglement' => ReglementClient::TYPE_REGLEMENT]);
        $this->assertSame('Règlement', $reglement->type_reglement_libelle);
        $this->assertSame('primary', $reglement->type_reglement_couleur);

        $perte = ReglementClientFactory::new()->perte()->create();
        $this->assertSame('Perte', $perte->type_reglement_libelle);
        $this->assertSame('danger', $perte->type_reglement_couleur);
    }

    public function test_get_institutions_retourne_des_valeurs_distinctes(): void
    {
        ReglementClientFactory::new()->create(['institution' => 'BOA']);
        ReglementClientFactory::new()->create(['institution' => 'BOA']);
        ReglementClientFactory::new()->create(['institution' => 'ECOBANK']);
        ReglementClientFactory::new()->create(['institution' => null]);

        $institutions = ReglementClient::getInstitutions();

        $this->assertEqualsCanonicalizing(['BOA', 'ECOBANK'], $institutions);
    }

    public function test_get_types_reglement(): void
    {
        $types = collect(ReglementClient::getTypesReglement())->pluck('value')->all();
        $this->assertEqualsCanonicalizing(['reglement', 'perte'], $types);
    }

    public function test_to_api_array_expose_la_relation_facture(): void
    {
        $reglement = ReglementClientFactory::new()->create(['montant' => 50000]);
        $api = $reglement->toApiArray();

        $this->assertEquals(50000.0, $api['montant']);
        $this->assertArrayHasKey('facture', $api);
        $this->assertArrayHasKey('client', $api);
    }
}
