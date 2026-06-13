<?php

namespace Tests\Feature;

use Database\Factories\ApprovisionnementBanqueFactory;
use Database\Factories\CompteBancaireFactory;
use Database\Factories\FactureClientFactory;
use Database\Factories\ReglementClientFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsPermissions;
use Tests\TestCase;

class BordereauApprovisionnementTest extends TestCase
{
    use RefreshDatabase;
    use SeedsPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();
    }

    public function test_creation_bordereau_refusee_sans_permission(): void
    {
        $this->actingAsWithPermissions(['banques.voir']);
        $compte = CompteBancaireFactory::new()->create();

        $this->postJson('/api/banques/approvisionnement', [
            'compte_bancaire_id' => $compte->id,
            'reference_bordereau' => 'BORD-001',
            'date_depot' => '2026-05-01',
            'montant' => 100000,
        ])->assertStatus(403);
    }

    public function test_creation_bordereau_credite_le_solde(): void
    {
        $this->actingAsWithPermissions(['banques.modifier']);
        $compte = CompteBancaireFactory::new()->create(['solde' => 50000]);

        $this->postJson('/api/banques/approvisionnement', [
            'compte_bancaire_id' => $compte->id,
            'reference_bordereau' => 'BORD-001',
            'date_depot' => '2026-05-01',
            'montant' => 100000,
        ])->assertSuccessful()->assertJson(['success' => true]);

        $this->assertDatabaseHas('approvisionnements_banques', [
            'compte_bancaire_id' => $compte->id,
            'reference_bordereau' => 'BORD-001',
            'montant' => 100000,
        ]);
        $this->assertEquals(150000, $compte->fresh()->solde);
    }

    public function test_modification_bordereau_ajuste_le_solde_par_delta(): void
    {
        $this->actingAsWithPermissions(['banques.modifier']);
        $compte = CompteBancaireFactory::new()->create(['solde' => 200000]);
        $appro = ApprovisionnementBanqueFactory::new()->create([
            'compte_bancaire_id' => $compte->id,
            'montant' => 100000,
            'reference_bordereau' => 'BORD-AVANT',
        ]);

        // On passe le montant de 100 000 à 130 000 → le solde doit augmenter de 30 000.
        $this->putJson("/api/banques/approvisionnement/{$appro->id}", [
            'reference_bordereau' => 'BORD-APRES',
            'date_depot' => '2026-06-01',
            'montant' => 130000,
            'observations' => 'Corrigé',
        ])->assertSuccessful()->assertJson(['success' => true]);

        $appro->refresh();
        $this->assertSame('BORD-APRES', $appro->reference_bordereau);
        $this->assertEquals(130000, $appro->montant);
        $this->assertSame('2026-06-01', $appro->date_depot->format('Y-m-d'));
        $this->assertEquals(230000, $compte->fresh()->solde);
    }

    public function test_modification_diminution_sous_le_montant_impute_rejetee(): void
    {
        $this->actingAsWithPermissions(['banques.modifier']);
        $compte = CompteBancaireFactory::new()->create(['solde' => 200000]);
        $appro = ApprovisionnementBanqueFactory::new()->create([
            'compte_bancaire_id' => $compte->id,
            'montant' => 100000,
        ]);
        // 80 000 déjà imputés par un règlement client sur ce bordereau
        $facture = FactureClientFactory::new()->create(['montant' => 80000, 'ristourne' => 0]);
        ReglementClientFactory::new()->pourFacture($facture)->create([
            'approvisionnement_id' => $appro->id,
            'montant' => 80000,
        ]);

        $this->putJson("/api/banques/approvisionnement/{$appro->id}", [
            'reference_bordereau' => 'BORD-X',
            'date_depot' => '2026-06-01',
            'montant' => 50000, // < 80 000 imputés
        ])->assertStatus(422)->assertJson(['success' => false]);

        // Le bordereau n'a pas changé
        $this->assertEquals(100000, $appro->fresh()->montant);
    }

    public function test_suppression_bordereau_refusee_si_reglements_imputes(): void
    {
        $this->actingAsWithPermissions(['banques.supprimer']);
        $appro = ApprovisionnementBanqueFactory::new()->create(['montant' => 100000]);
        $facture = FactureClientFactory::new()->create();
        ReglementClientFactory::new()->pourFacture($facture)->create([
            'approvisionnement_id' => $appro->id,
            'montant' => 10000,
        ]);

        $this->deleteJson("/api/banques/approvisionnement/{$appro->id}")
            ->assertStatus(422)->assertJson(['success' => false]);

        $this->assertDatabaseHas('approvisionnements_banques', ['id' => $appro->id]);
    }

    public function test_suppression_bordereau_debite_le_solde(): void
    {
        $this->actingAsWithPermissions(['banques.supprimer']);
        $compte = CompteBancaireFactory::new()->create(['solde' => 150000]);
        $appro = ApprovisionnementBanqueFactory::new()->create([
            'compte_bancaire_id' => $compte->id,
            'montant' => 100000,
        ]);

        $this->deleteJson("/api/banques/approvisionnement/{$appro->id}")->assertSuccessful();

        $this->assertSoftDeleted('approvisionnements_banques', ['id' => $appro->id]);
        $this->assertEquals(50000, $compte->fresh()->solde);
    }
}
