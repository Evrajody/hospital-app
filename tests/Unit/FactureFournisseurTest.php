<?php

namespace Tests\Unit;

use App\Models\FactureFournisseur;
use Database\Factories\FactureFournisseurFactory;
use Database\Factories\FournisseurFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FactureFournisseurTest extends TestCase
{
    use RefreshDatabase;

    public function test_calcul_des_montants_sans_tva_sans_reduction(): void
    {
        $f = FactureFournisseurFactory::new()->create([
            'montant_facture' => 100000,
            'assujetti_tva' => false,
            'montant_mo' => 0,
            'taux' => 0,
            'avoir' => 0,
        ]);

        $this->assertEquals(100000, $f->montant_ht);
        $this->assertEquals(0, $f->montant_tva);
        $this->assertEquals(100000, $f->montant_ttc);
        $this->assertEquals(0, $f->montant_reduction);
        // NAP = facture - avoir - AIB
        $this->assertEquals(100000, $f->montant_net);
        $this->assertEquals(100000, $f->reste_a_payer);
    }

    public function test_calcul_tva_informative_sur_le_ht(): void
    {
        $f = FactureFournisseurFactory::new()->create([
            'montant_facture' => 100000,
            'assujetti_tva' => true,
            'taux_tva' => 18,
        ]);

        $this->assertEquals(18000, $f->montant_tva);
        $this->assertEquals(118000, $f->montant_ttc);
        // La TVA ne réduit pas le net à payer (modèle "TVA pour compte")
        $this->assertEquals(100000, $f->montant_net);
    }

    public function test_calcul_aib_sur_montant_mo_et_avoir(): void
    {
        $f = FactureFournisseurFactory::new()->create([
            'montant_facture' => 100000,
            'montant_mo' => 50000,
            'taux' => 5, // AIB 5% de la MO = 2500
            'avoir' => 1000,
            'assujetti_tva' => false,
        ]);

        $this->assertEquals(2500, $f->montant_reduction);
        // NAP = 100000 - 1000 (avoir) - 2500 (AIB) = 96500
        $this->assertEquals(96500, $f->montant_net);
        $this->assertEquals(96500, $f->reste_a_payer);
    }

    public function test_enregistrer_paiement_partiel_puis_total(): void
    {
        $f = FactureFournisseurFactory::new()->create([
            'montant_facture' => 100000,
            'assujetti_tva' => false,
        ]);

        $this->assertTrue($f->enregistrerPaiement(40000));
        $this->assertEquals(40000, $f->montant_paye);
        $this->assertEquals(60000, $f->reste_a_payer);
        $this->assertSame(FactureFournisseur::STATUT_PARTIELLEMENT_PAYEE, $f->statut);

        $this->assertTrue($f->enregistrerPaiement(60000));
        $this->assertEquals(0, $f->reste_a_payer);
        $this->assertSame(FactureFournisseur::STATUT_PAYEE, $f->statut);
    }

    public function test_paiement_avec_tolerance_d_arrondi(): void
    {
        $f = FactureFournisseurFactory::new()->create([
            'montant_facture' => 100000,
            'assujetti_tva' => false,
        ]);

        $f->enregistrerPaiement(99999.995);

        $this->assertSame(FactureFournisseur::STATUT_PAYEE, $f->statut);
        $this->assertEquals(0, $f->reste_a_payer);
    }

    public function test_une_facture_payee_ne_peut_plus_etre_payee(): void
    {
        $f = FactureFournisseurFactory::new()->payee()->create([
            'montant_facture' => 100000,
            'assujetti_tva' => false,
        ]);

        $this->assertFalse($f->peut_etre_payee);
        $this->assertFalse($f->enregistrerPaiement(1000));
    }

    public function test_annuler_une_facture_non_payee(): void
    {
        $f = FactureFournisseurFactory::new()->create(['statut' => FactureFournisseur::STATUT_VALIDEE]);
        $this->assertTrue($f->annuler());
        $this->assertSame(FactureFournisseur::STATUT_ANNULEE, $f->statut);
    }

    public function test_annuler_une_facture_payee_echoue(): void
    {
        $f = FactureFournisseurFactory::new()->payee()->create();
        $this->assertFalse($f->annuler());
        $this->assertSame(FactureFournisseur::STATUT_PAYEE, $f->statut);
    }

    public function test_generer_numero_piece_incremente_la_sequence(): void
    {
        $annee = substr(date('Y'), -3);
        FactureFournisseurFactory::new()->create(['numero_piece' => "PC/{$annee}/0001"]);

        $numero = FactureFournisseur::genererNumeroPiece();

        $this->assertSame("PC/{$annee}/0002", $numero);
    }

    public function test_generer_numero_piece_premier_numero(): void
    {
        $annee = substr(date('Y'), -3);
        $this->assertSame("PC/{$annee}/0001", FactureFournisseur::genererNumeroPiece());
    }

    public function test_accesseurs_statut_libelle_et_couleur(): void
    {
        $f = FactureFournisseurFactory::new()->payee()->create();
        $this->assertSame('Payée', $f->statut_libelle);
        $this->assertSame('success', $f->statut_couleur);
    }

    public function test_scopes_de_statut(): void
    {
        FactureFournisseurFactory::new()->create(['statut' => FactureFournisseur::STATUT_VALIDEE]);
        FactureFournisseurFactory::new()->payee()->create();
        FactureFournisseurFactory::new()->create(['statut' => FactureFournisseur::STATUT_PARTIELLEMENT_PAYEE]);

        $this->assertSame(1, FactureFournisseur::query()->payee()->count());
        $this->assertSame(1, FactureFournisseur::query()->validee()->count());
        // nonPayee = validee + partiellement_payee
        $this->assertSame(2, FactureFournisseur::query()->nonPayee()->count());
    }

    public function test_statistiques_globales(): void
    {
        $f = FournisseurFactory::new()->create();
        FactureFournisseurFactory::new()->create([
            'fournisseur_id' => $f->id,
            'montant_facture' => 100000,
            'assujetti_tva' => false,
            'statut' => FactureFournisseur::STATUT_VALIDEE,
        ]);
        FactureFournisseurFactory::new()->payee()->create([
            'fournisseur_id' => $f->id,
            'montant_facture' => 50000,
            'assujetti_tva' => false,
        ]);

        $stats = FactureFournisseur::getStatistiques($f->id);

        $this->assertSame(2, $stats['total']);
        $this->assertSame(1, $stats['payees']);
        $this->assertEquals(150000, $stats['montant_total']);
    }
}
