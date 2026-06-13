<?php

namespace Tests\Feature;

use Database\Factories\CompteComptableFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsPermissions;
use Tests\TestCase;

class PlanComptableApiTest extends TestCase
{
    use RefreshDatabase;
    use SeedsPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();
    }

    public function test_creation_refusee_sans_permission_modifier(): void
    {
        $this->actingAsWithPermissions(['plan-comptable.voir']);
        $this->postJson('/api/plan-comptable', ['numero_compte' => '4015', 'libelle' => 'Test'])
            ->assertStatus(403);
    }

    public function test_creation_compte_personnalise(): void
    {
        $this->actingAsWithPermissions(['plan-comptable.modifier']);

        $this->postJson('/api/plan-comptable', [
            'numero_compte' => '60125',
            'libelle' => 'Achats spécifiques',
        ])->assertSuccessful()->assertJson(['success' => true]);

        $this->assertDatabaseHas('plan_comptable_ohada', [
            'numero_compte' => '60125',
            'classe' => 6,
            'is_custom' => true,
        ]);
    }

    public function test_numero_doit_commencer_par_le_parent(): void
    {
        $this->actingAsWithPermissions(['plan-comptable.modifier']);
        $parent = CompteComptableFactory::new()->numero('601')->create();

        $this->postJson('/api/plan-comptable', [
            'numero_compte' => '7011',
            'libelle' => 'Incohérent',
            'parent_id' => $parent->id,
        ])->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_numero_compte_unique(): void
    {
        $this->actingAsWithPermissions(['plan-comptable.modifier']);
        CompteComptableFactory::new()->numero('5211')->create();

        $this->postJson('/api/plan-comptable', ['numero_compte' => '5211', 'libelle' => 'Doublon'])
            ->assertStatus(422)->assertJsonValidationErrors('numero_compte');
    }

    public function test_suppression_refusee_si_sous_comptes(): void
    {
        $this->actingAsWithPermissions(['plan-comptable.supprimer']);
        $parent = CompteComptableFactory::new()->numero('40')->create();
        CompteComptableFactory::new()->numero('401')->create(['parent_id' => $parent->id]);

        $this->deleteJson("/api/plan-comptable/{$parent->id}")
            ->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_suppression_compte_feuille(): void
    {
        $this->actingAsWithPermissions(['plan-comptable.supprimer']);
        $compte = CompteComptableFactory::new()->numero('60150')->create();

        $this->deleteJson("/api/plan-comptable/{$compte->id}")->assertSuccessful();
        $this->assertDatabaseMissing('plan_comptable_ohada', ['id' => $compte->id]);
    }
}
