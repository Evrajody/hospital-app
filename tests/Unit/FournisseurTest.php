<?php

namespace Tests\Unit;

use App\Models\Fournisseur;
use Database\Factories\CompteComptableFactory;
use Database\Factories\FournisseurFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FournisseurTest extends TestCase
{
    use RefreshDatabase;

    public function test_libelle_du_type_fournisseur(): void
    {
        $f = FournisseurFactory::new()->create(['type_fournisseur' => Fournisseur::TYPE_MEDICAMENTS]);
        $this->assertSame('Médicaments & Produits pharmaceutiques', $f->type_fournisseur_libelle);

        $autre = FournisseurFactory::new()->create(['type_fournisseur' => null]);
        $this->assertSame('Non défini', $autre->type_fournisseur_libelle);
    }

    public function test_nom_du_pays(): void
    {
        $this->assertSame('Bénin', FournisseurFactory::new()->create(['pays' => 'BJ'])->pays_nom);
        $this->assertSame('XX', FournisseurFactory::new()->create(['pays' => 'XX'])->pays_nom);
    }

    public function test_scopes_avec_et_sans_compte(): void
    {
        $compte = CompteComptableFactory::new()->numero('4011')->create();
        FournisseurFactory::new()->create(['compte_comptable_id' => $compte->id]);
        FournisseurFactory::new()->create(['compte_comptable_id' => null]);

        $this->assertSame(1, Fournisseur::avecCompte()->count());
        $this->assertSame(1, Fournisseur::sansCompte()->count());
    }

    public function test_scope_type(): void
    {
        FournisseurFactory::new()->create(['type_fournisseur' => Fournisseur::TYPE_SERVICES]);
        FournisseurFactory::new()->create(['type_fournisseur' => Fournisseur::TYPE_MEDICAMENTS]);

        $this->assertSame(1, Fournisseur::type(Fournisseur::TYPE_SERVICES)->count());
    }

    public function test_a_compte_comptable_et_numero(): void
    {
        $compte = CompteComptableFactory::new()->numero('4012')->create();
        $f = FournisseurFactory::new()->create(['compte_comptable_id' => $compte->id]);

        $this->assertTrue($f->aCompteComptable());
        $this->assertSame('4012', $f->getNumeroCompte());

        $sans = FournisseurFactory::new()->create(['compte_comptable_id' => null]);
        $this->assertFalse($sans->aCompteComptable());
        $this->assertNull($sans->getNumeroCompte());
    }

    public function test_recherche_ilike(): void
    {
        FournisseurFactory::new()->create(['nom' => 'Pharmacie Centrale']);
        FournisseurFactory::new()->create(['nom' => 'Clinique du Lac']);

        $resultats = Fournisseur::recherche('pharma')->get();

        $this->assertCount(1, $resultats);
        $this->assertSame('Pharmacie Centrale', $resultats->first()->nom);
    }

    public function test_statistiques_par_type(): void
    {
        FournisseurFactory::new()->count(2)->create(['type_fournisseur' => Fournisseur::TYPE_MEDICAMENTS]);
        FournisseurFactory::new()->create(['type_fournisseur' => Fournisseur::TYPE_SERVICES]);

        $stats = Fournisseur::getStatistiques();

        $this->assertSame(3, $stats['total']);
        $this->assertSame(2, $stats['par_type'][Fournisseur::TYPE_MEDICAMENTS]);
    }
}
