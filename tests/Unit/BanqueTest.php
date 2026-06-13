<?php

namespace Tests\Unit;

use Database\Factories\BanqueFactory;
use Database\Factories\CompteBancaireFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BanqueTest extends TestCase
{
    use RefreshDatabase;

    public function test_solde_total_somme_les_comptes(): void
    {
        $banque = BanqueFactory::new()->create();
        CompteBancaireFactory::new()->create(['banque_id' => $banque->id, 'solde' => 100000]);
        CompteBancaireFactory::new()->create(['banque_id' => $banque->id, 'solde' => 50000]);

        $this->assertEquals(150000, $banque->solde_total);
    }

    public function test_solde_total_sans_compte_est_zero(): void
    {
        $banque = BanqueFactory::new()->create();
        $this->assertEquals(0, $banque->solde_total);
    }
}
