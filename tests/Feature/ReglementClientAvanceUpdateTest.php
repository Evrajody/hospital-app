<?php

namespace Tests\Feature;

use Database\Factories\AvanceClientFactory;
use Database\Factories\ClientFactory;
use Database\Factories\FactureClientFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsPermissions;
use Tests\TestCase;

/**
 * Vérifie que la modification d'un règlement client imputé sur une avance
 * recalcule correctement le solde de l'avance (correctif de l'édition).
 */
class ReglementClientAvanceUpdateTest extends TestCase
{
    use RefreshDatabase;
    use SeedsPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();
    }

    public function test_modification_reglement_impute_recalcule_solde_avance(): void
    {
        $this->actingAsWithPermissions(['reglements-clients.creer', 'reglements-clients.modifier']);

        $client = ClientFactory::new()->create();
        $facture = FactureClientFactory::new()->create([
            'client_id' => $client->id,
            'montant' => 100000,
            'montant_paye' => 0,
        ]);
        $avance = AvanceClientFactory::new()->create([
            'client_id' => $client->id,
            'montant' => 300000,
            'montant_utilise' => 0,
        ]);

        // 1) Création : règlement de 50 000 imputé sur l'avance.
        $reglementId = $this->postJson('/api/reglements-clients', [
            'facture_id' => $facture->id,
            'date_reglement' => '2026-06-01',
            'montant' => 50000,
            'avance_id' => $avance->id,
        ])->assertCreated()->json('data.id');

        $avance->refresh();
        $this->assertEquals(50000, (float) $avance->montant_utilise, 'Après création : 50 000 utilisés');

        // 2) Modification du montant à 80 000 → l'avance doit suivre.
        $this->putJson("/api/reglements-clients/{$reglementId}", [
            'date_reglement' => '2026-06-01',
            'montant' => 80000,
            'avance_id' => $avance->id,
        ])->assertSuccessful();

        $avance->refresh();
        $this->assertEquals(80000, (float) $avance->montant_utilise, 'Après modif : 80 000 utilisés (recalcul)');

        // 3) Retrait de l'imputation (passage en paiement direct) → avance libérée.
        $this->putJson("/api/reglements-clients/{$reglementId}", [
            'date_reglement' => '2026-06-01',
            'montant' => 80000,
            'institution' => 'Espèces',
        ])->assertSuccessful();

        $avance->refresh();
        $this->assertEquals(0, (float) $avance->montant_utilise, 'Après retrait : avance libérée');
        $this->assertEquals('disponible', $avance->statut);
    }

    public function test_modification_refusee_si_depasse_solde_avance(): void
    {
        $this->actingAsWithPermissions(['reglements-clients.creer', 'reglements-clients.modifier']);

        $client = ClientFactory::new()->create();
        $facture = FactureClientFactory::new()->create([
            'client_id' => $client->id,
            'montant' => 500000,
            'montant_paye' => 0,
        ]);
        $avance = AvanceClientFactory::new()->create([
            'client_id' => $client->id,
            'montant' => 100000,
            'montant_utilise' => 0,
        ]);

        $reglementId = $this->postJson('/api/reglements-clients', [
            'facture_id' => $facture->id,
            'date_reglement' => '2026-06-01',
            'montant' => 60000,
            'avance_id' => $avance->id,
        ])->assertCreated()->json('data.id');

        // Le solde de l'avance est 100 000 ; tenter 150 000 doit échouer.
        $this->putJson("/api/reglements-clients/{$reglementId}", [
            'date_reglement' => '2026-06-01',
            'montant' => 150000,
            'avance_id' => $avance->id,
        ])->assertStatus(422);
    }
}
