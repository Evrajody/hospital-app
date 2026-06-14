<?php

namespace Tests\Feature;

use App\Console\Commands\LegacyMigrate;
use App\Models\FactureClient;
use App\Models\FactureFournisseur;
use Database\Factories\FactureClientFactory;
use Database\Factories\FactureFournisseurFactory;
use Database\Factories\ReglementClientFactory;
use Database\Factories\ReglementFournisseurFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verrouille la correction du bug : une facture marquée SOLDÉE à la source
 * (date_solde renseignée) doit finir en 'payee' avec reste_a_payer = 0, même si
 * le détail des règlements est inférieur au montant (déficit), tout en conservant
 * montant_paye = somme réelle des règlements.
 */
class LegacyMigrateSoldeTest extends TestCase
{
    use RefreshDatabase;

    private function recompute(): void
    {
        $m = new \ReflectionMethod(LegacyMigrate::class, 'recomputeSoldes');
        $m->setAccessible(true);
        $m->invoke(new LegacyMigrate());
    }

    private function invoke(string $method, ...$args)
    {
        $m = new \ReflectionMethod(LegacyMigrate::class, $method);
        $m->setAccessible(true);
        return $m->invoke(new LegacyMigrate(), ...$args);
    }

    public function test_sane_date_rejette_les_annees_aberrantes(): void
    {
        // Années aberrantes (fautes de saisie source) → null
        $this->assertNull($this->invoke('saneDate', '3017-11-30 00:00:00'));
        $this->assertNull($this->invoke('saneDate', '4202-05-05 00:00:00'));
        $this->assertNull($this->invoke('saneDate', '1900-01-01 00:00:00'));
        $this->assertNull($this->invoke('saneDate', '2031-01-01 00:00:00')); // futur improbable
        // Années plausibles → conservées
        $this->assertSame('2017-11-30', $this->invoke('saneDate', '2017-11-30 00:00:00'));
        $this->assertSame('2024-06-14', $this->invoke('saneDate', '2024-06-14 00:00:00'));

        $this->assertSame('2017-01-01', $this->invoke('yearToDate', 2017));
        $this->assertNull($this->invoke('yearToDate', 3017));
    }

    public function test_resolution_date_facture_pc_017_2048(): void
    {
        // Cas réel : datfac='3017-11-30' (typo), datenreg='2017-11-30' (correct), annee=2017
        $dateFac = $this->invoke('saneDate', '3017-11-30 00:00:00')
            ?: $this->invoke('saneDate', '2017-11-30 00:00:00')
            ?: $this->invoke('yearToDate', 2017);

        $this->assertSame('2017-11-30', $dateFac); // et NON 3017-11-30
    }

    public function test_facture_client_soldee_mais_partiellement_reglee_devient_payee(): void
    {
        // montant 1000, soldée (date_solde), mais seulement 600 réglés → déficit 400
        $f = FactureClientFactory::new()->create([
            'montant' => 1000, 'ristourne' => 0, 'date_solde' => '2012-06-04',
        ]);
        ReglementClientFactory::new()->pourFacture($f)->create(['montant' => 600, 'type_reglement' => 'reglement']);

        $this->recompute();

        $f->refresh();
        $this->assertSame(FactureClient::STATUT_PAYEE, $f->statut);
        $this->assertEquals(0, $f->reste_a_payer);
        $this->assertEquals(600, $f->montant_paye); // déficit lisible : 1000 - 600
    }

    public function test_facture_client_soldee_sans_reglement_devient_payee(): void
    {
        $f = FactureClientFactory::new()->create([
            'montant' => 1000, 'ristourne' => 0, 'date_solde' => '2012-06-04',
        ]);

        $this->recompute();

        $f->refresh();
        $this->assertSame(FactureClient::STATUT_PAYEE, $f->statut);
        $this->assertEquals(0, $f->reste_a_payer);
        $this->assertEquals(0, $f->montant_paye);
    }

    public function test_facture_client_non_soldee_reste_partiellement_payee(): void
    {
        $f = FactureClientFactory::new()->create([
            'montant' => 1000, 'ristourne' => 0, 'date_solde' => null,
        ]);
        ReglementClientFactory::new()->pourFacture($f)->create(['montant' => 400, 'type_reglement' => 'reglement']);

        $this->recompute();

        $f->refresh();
        $this->assertSame(FactureClient::STATUT_PARTIELLEMENT_PAYEE, $f->statut);
        $this->assertEquals(600, $f->reste_a_payer);
    }

    public function test_facture_fournisseur_soldee_partielle_devient_payee(): void
    {
        $f = FactureFournisseurFactory::new()->create([
            'montant_facture' => 1000, 'assujetti_tva' => false, 'date_solde' => '2012-06-04',
            'statut' => FactureFournisseur::STATUT_VALIDEE,
        ]);
        ReglementFournisseurFactory::new()->pourFacture($f)->create(['montant' => 700]);

        $this->recompute();

        $f->refresh();
        $this->assertSame(FactureFournisseur::STATUT_PAYEE, $f->statut);
        $this->assertEquals(0, $f->reste_a_payer);
        $this->assertEquals(700, $f->montant_paye);
    }
}
