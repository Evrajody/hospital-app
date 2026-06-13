<?php

namespace Tests\Unit;

use App\Models\CompteComptable;
use Database\Factories\CompteComptableFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompteComptableTest extends TestCase
{
    use RefreshDatabase;

    public function test_classe_libelle(): void
    {
        $compte = CompteComptableFactory::new()->numero('4')->create();
        $compte->classe = CompteComptable::CLASSE_TIERS;
        $this->assertSame('Tiers', $compte->classe_libelle);
    }

    public function test_est_racine(): void
    {
        $racine = CompteComptableFactory::new()->numero('4')->create(['niveau' => 1]);
        $enfant = CompteComptableFactory::new()->numero('40')->create(['niveau' => 2]);

        $this->assertTrue($racine->est_racine);
        $this->assertFalse($enfant->est_racine);
    }

    public function test_hierarchie_par_prefixe_de_numero(): void
    {
        // La hiérarchie est dérivée du préfixe du numéro de compte (countDescendants,
        // profondeur, numero_compte_parent), c'est ce que l'application utilise réellement.
        $c4 = CompteComptableFactory::new()->numero('4')->create();
        CompteComptableFactory::new()->numero('40')->create();
        CompteComptableFactory::new()->numero('401')->create();
        $c4011 = CompteComptableFactory::new()->numero('4011')->create();

        // descendants de 4 = 40, 401, 4011
        $this->assertSame(3, $c4->countDescendants());

        // profondeur = longueur du numéro
        $this->assertSame(4, $c4011->profondeur);

        // numéro du parent = numéro tronqué d'un caractère
        $this->assertSame('401', $c4011->numero_compte_parent);
        $this->assertNull($c4->numero_compte_parent);
    }

    public function test_est_feuille(): void
    {
        CompteComptableFactory::new()->numero('60')->create();
        $feuille = CompteComptableFactory::new()->numero('601')->create();

        // 60 a un descendant (601) → pas feuille ; 601 n'en a pas → feuille
        $this->assertFalse(CompteComptable::where('numero_compte', '60')->first()->est_feuille);
        $this->assertTrue($feuille->est_feuille);
    }

    public function test_est_ancetre_et_est_descendant(): void
    {
        $c40 = CompteComptableFactory::new()->numero('40')->create();
        $c401 = CompteComptableFactory::new()->numero('401')->create();

        $this->assertTrue($c40->estAncetre($c401));
        $this->assertTrue($c401->estDescendant($c40));
        $this->assertFalse($c401->estAncetre($c40));
    }

    public function test_valider_numero_compte_existant(): void
    {
        CompteComptableFactory::new()->numero('411')->create();

        $resultat = CompteComptable::validerNumeroCompte('411');

        $this->assertTrue($resultat['valide']);
        $this->assertNotNull($resultat['compte']);
    }

    public function test_valider_numero_compte_inexistant_mais_parent_present(): void
    {
        CompteComptableFactory::new()->numero('41')->create();

        $resultat = CompteComptable::validerNumeroCompte('411');

        $this->assertFalse($resultat['valide']);
        $this->assertArrayHasKey('parent', $resultat);
        $this->assertSame('41', $resultat['parent']->numero_compte);
    }

    public function test_scopes_classe_et_commence_par(): void
    {
        CompteComptableFactory::new()->numero('411')->create();
        CompteComptableFactory::new()->numero('401')->create();
        CompteComptableFactory::new()->numero('601')->create();

        $this->assertSame(2, CompteComptable::classe(4)->count());
        $this->assertSame(1, CompteComptable::commencePar('41')->count());
    }

    public function test_helpers_clients_fournisseurs_banques(): void
    {
        CompteComptableFactory::new()->numero('411')->create();
        CompteComptableFactory::new()->numero('401')->create();
        CompteComptableFactory::new()->numero('521')->create();

        $this->assertSame('411', CompteComptable::clients()->first()->numero_compte);
        $this->assertSame('401', CompteComptable::fournisseurs()->first()->numero_compte);
        $this->assertSame('521', CompteComptable::banques()->first()->numero_compte);
    }

    public function test_format_long(): void
    {
        $compte = CompteComptableFactory::new()->numero('601', 'Achats')->create();
        $this->assertSame('601 - Achats', $compte->getFormatLong());
    }
}
