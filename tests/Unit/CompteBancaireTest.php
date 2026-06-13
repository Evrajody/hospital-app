<?php

namespace Tests\Unit;

use App\Models\ApprovisionnementBanque;
use Database\Factories\BanqueFactory;
use Database\Factories\CompteBancaireFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompteBancaireTest extends TestCase
{
    use RefreshDatabase;

    public function test_debiter_diminue_le_solde(): void
    {
        $compte = CompteBancaireFactory::new()->create(['solde' => 100000]);

        $compte->debiter(30000);

        $this->assertEquals(70000, $compte->fresh()->solde);
    }

    public function test_solde_est_suffisant(): void
    {
        $compte = CompteBancaireFactory::new()->create(['solde' => 100000]);

        $this->assertTrue($compte->soldeEstSuffisant(100000));
        $this->assertFalse($compte->soldeEstSuffisant(100001));
    }

    public function test_approvisionner_credite_le_solde_et_cree_un_approvisionnement(): void
    {
        $compte = CompteBancaireFactory::new()->create(['solde' => 50000]);

        $appro = $compte->approvisionner(25000, '2026-05-01', 'dépôt espèces', null, 'BORD-2026-01');

        $this->assertInstanceOf(ApprovisionnementBanque::class, $appro);
        $this->assertEquals(75000, $compte->fresh()->solde);
        $this->assertEquals(25000, $appro->montant);
        $this->assertSame('BORD-2026-01', $appro->reference_bordereau);
        $this->assertDatabaseHas('approvisionnements_banques', [
            'compte_bancaire_id' => $compte->id,
            'montant' => 25000,
        ]);
    }

    public function test_relation_banque(): void
    {
        $banque = BanqueFactory::new()->create();
        $compte = CompteBancaireFactory::new()->create(['banque_id' => $banque->id]);

        $this->assertTrue($compte->banque->is($banque));
    }
}
