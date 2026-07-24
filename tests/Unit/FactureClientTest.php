<?php

namespace Tests\Unit;

use App\Models\FactureClient;
use Database\Factories\FactureClientFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FactureClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_boot_calcule_le_reste_a_payer_avec_ristourne(): void
    {
        $f = FactureClientFactory::new()->create([
            'montant' => 100000,
            'ristourne' => 10000,
            'montant_paye' => 0,
        ]);

        // reste = montant - ristourne - paye
        $this->assertEquals(90000, $f->reste_a_payer);
    }

    public function test_enregistrer_paiement_partiel(): void
    {
        $f = FactureClientFactory::new()->create(['montant' => 100000, 'ristourne' => 0]);

        $this->assertTrue($f->enregistrerPaiement(30000));
        $this->assertEquals(30000, $f->montant_paye);
        $this->assertEquals(70000, $f->reste_a_payer);
        $this->assertSame(FactureClient::STATUT_PARTIELLEMENT_PAYEE, $f->statut);
    }

    public function test_enregistrer_paiement_total_tient_compte_de_la_ristourne(): void
    {
        $f = FactureClientFactory::new()->create(['montant' => 100000, 'ristourne' => 10000]);

        // Net à payer = 90000
        $this->assertTrue($f->enregistrerPaiement(90000));
        $this->assertSame(FactureClient::STATUT_PAYEE, $f->statut);
        $this->assertEquals(0, $f->reste_a_payer);
    }

    public function test_un_trop_percu_conserve_un_solde_negatif(): void
    {
        $f = FactureClientFactory::new()->create(['montant' => 100000, 'ristourne' => 0]);

        $this->assertTrue($f->enregistrerPaiement(120000));
        $this->assertSame(FactureClient::STATUT_PAYEE, $f->statut);
        $this->assertEquals(-20000, $f->reste_a_payer);
    }

    public function test_generer_reference_format_et_increment(): void
    {
        $mois = date('m');
        $annee = date('y');
        FactureClientFactory::new()->create(['reference' => "0001/{$mois}/{$annee}"]);

        $ref = FactureClient::genererReference();

        $this->assertSame("0002/{$mois}/{$annee}", $ref);
    }

    public function test_generer_reference_premiere(): void
    {
        $mois = date('m');
        $annee = date('y');
        $this->assertSame("0001/{$mois}/{$annee}", FactureClient::genererReference());
    }

    public function test_to_api_array_calcule_net_a_payer(): void
    {
        $f = FactureClientFactory::new()->create(['montant' => 100000, 'ristourne' => 25000]);

        $api = $f->toApiArray();

        $this->assertEquals(75000, $api['net_a_payer']);
        $this->assertEquals(100000, $api['montant']);
        $this->assertEquals(25000, $api['ristourne']);
    }
}
