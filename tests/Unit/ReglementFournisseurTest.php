<?php

namespace Tests\Unit;

use App\Models\FactureFournisseur;
use App\Models\ReglementFournisseur;
use Database\Factories\FactureFournisseurFactory;
use Database\Factories\ReglementFournisseurFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReglementFournisseurTest extends TestCase
{
    use RefreshDatabase;

    public function test_valider_un_reglement(): void
    {
        $user = \Database\Factories\UserFactory::new()->create();
        $r = ReglementFournisseurFactory::new()->create(['statut' => ReglementFournisseur::STATUT_EN_ATTENTE]);

        $this->assertTrue($r->valider($user->id));
        $this->assertSame(ReglementFournisseur::STATUT_VALIDE, $r->statut);
        $this->assertSame($user->id, $r->validated_by);
        $this->assertNotNull($r->validated_at);
    }

    public function test_valider_un_reglement_annule_echoue(): void
    {
        $r = ReglementFournisseurFactory::new()->create(['statut' => ReglementFournisseur::STATUT_ANNULE]);
        $this->assertFalse($r->valider());
    }

    public function test_annuler_un_reglement_restaure_le_solde_de_la_facture(): void
    {
        $facture = FactureFournisseurFactory::new()->create([
            'montant_facture' => 100000,
            'assujetti_tva' => false,
        ]);
        $facture->enregistrerPaiement(40000);
        $this->assertSame(FactureFournisseur::STATUT_PARTIELLEMENT_PAYEE, $facture->statut);

        $r = ReglementFournisseurFactory::new()->pourFacture($facture)->create(['montant' => 40000]);

        $this->assertTrue($r->annuler());
        $this->assertSame(ReglementFournisseur::STATUT_ANNULE, $r->statut);

        $facture->refresh();
        $this->assertEquals(0, $facture->montant_paye);
        $this->assertEquals(100000, $facture->reste_a_payer);
        $this->assertSame(FactureFournisseur::STATUT_VALIDEE, $facture->statut);
    }

    public function test_annuler_un_reglement_deja_annule_echoue(): void
    {
        $r = ReglementFournisseurFactory::new()->create(['statut' => ReglementFournisseur::STATUT_ANNULE]);
        $this->assertFalse($r->annuler());
    }

    public function test_generer_numero_reglement_incremente(): void
    {
        $annee = substr(date('Y'), -2);
        ReglementFournisseurFactory::new()->create(['numero_reglement' => "REG/{$annee}/00001"]);

        $this->assertSame("REG/{$annee}/00002", ReglementFournisseur::genererNumeroReglement());
    }

    public function test_accesseurs_libelles(): void
    {
        $r = ReglementFournisseurFactory::new()->create([
            'mode_paiement' => ReglementFournisseur::MODE_CHEQUE,
            'statut' => ReglementFournisseur::STATUT_VALIDE,
        ]);

        $this->assertSame('Chèque', $r->mode_paiement_libelle);
        $this->assertSame('Validé', $r->statut_libelle);
        $this->assertSame('success', $r->statut_couleur);
        $this->assertTrue($r->est_modifiable);
    }

    public function test_un_reglement_annule_n_est_pas_modifiable(): void
    {
        $r = ReglementFournisseurFactory::new()->create(['statut' => ReglementFournisseur::STATUT_ANNULE]);
        $this->assertFalse($r->est_modifiable);
    }

    public function test_statistiques_ne_comptent_que_les_valides(): void
    {
        $facture = FactureFournisseurFactory::new()->create();
        ReglementFournisseurFactory::new()->pourFacture($facture)->create([
            'montant' => 30000,
            'statut' => ReglementFournisseur::STATUT_VALIDE,
        ]);
        ReglementFournisseurFactory::new()->pourFacture($facture)->create([
            'montant' => 99999,
            'statut' => ReglementFournisseur::STATUT_ANNULE,
        ]);

        $stats = ReglementFournisseur::getStatistiques();

        $this->assertSame(1, $stats['nombre_reglements']);
        $this->assertEquals(30000, $stats['total_reglements']);
        $this->assertEquals(30000, $stats['montant_moyen']);
    }
}
