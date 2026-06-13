<?php

namespace Tests\Unit;

use App\Models\AvanceClient;
use Database\Factories\AvanceClientFactory;
use Database\Factories\ReglementClientFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvanceClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_montant_restant(): void
    {
        $avance = AvanceClientFactory::new()->create(['montant' => 300000, 'montant_utilise' => 100000]);
        $this->assertEquals(200000, $avance->montant_restant);
    }

    public function test_montant_restant_jamais_negatif(): void
    {
        $avance = AvanceClientFactory::new()->create(['montant' => 100000, 'montant_utilise' => 150000]);
        $this->assertEquals(0, $avance->montant_restant);
    }

    public function test_recalculer_solde_disponible(): void
    {
        $avance = AvanceClientFactory::new()->create(['montant' => 300000]);

        $avance->recalculerSolde();

        $this->assertSame(AvanceClient::STATUT_DISPONIBLE, $avance->statut);
        $this->assertEquals(0, $avance->montant_utilise);
    }

    public function test_recalculer_solde_partiellement_utilisee(): void
    {
        $avance = AvanceClientFactory::new()->create(['montant' => 300000]);
        ReglementClientFactory::new()->create(['avance_id' => $avance->id, 'montant' => 100000]);

        $avance->recalculerSolde();

        $this->assertSame(AvanceClient::STATUT_PARTIELLEMENT_UTILISEE, $avance->statut);
        $this->assertEquals(100000, $avance->montant_utilise);
    }

    public function test_recalculer_solde_epuisee(): void
    {
        $avance = AvanceClientFactory::new()->create(['montant' => 100000]);
        ReglementClientFactory::new()->create(['avance_id' => $avance->id, 'montant' => 100000]);

        $avance->recalculerSolde();

        $this->assertSame(AvanceClient::STATUT_EPUISEE, $avance->statut);
    }
}
