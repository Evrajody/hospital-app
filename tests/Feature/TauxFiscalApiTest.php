<?php

namespace Tests\Feature;

use App\Models\TauxFiscal;
use Database\Factories\TauxFiscalFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsPermissions;
use Tests\TestCase;

class TauxFiscalApiTest extends TestCase
{
    use RefreshDatabase;
    use SeedsPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();
    }

    public function test_creation_refusee_sans_permission_parametres_modifier(): void
    {
        $this->actingAsWithPermissions(['parametres.voir']);
        $this->postJson('/api/taux-fiscaux', ['type' => 'tva', 'libelle' => 'TVA', 'taux' => 18])
            ->assertStatus(403);
    }

    public function test_creation(): void
    {
        $this->actingAsWithPermissions(['parametres.modifier']);

        $this->postJson('/api/taux-fiscaux', [
            'type' => 'aib',
            'libelle' => 'AIB 1%',
            'taux' => 1,
            'par_defaut' => true,
        ])->assertCreated()->assertJson(['success' => true]);

        $this->assertDatabaseHas('taux_fiscaux', ['libelle' => 'AIB 1%', 'par_defaut' => true]);
    }

    public function test_par_defaut_est_exclusif_par_type(): void
    {
        $this->actingAsWithPermissions(['parametres.modifier']);
        $ancien = TauxFiscalFactory::new()->create(['type' => 'tva', 'par_defaut' => true]);

        $this->postJson('/api/taux-fiscaux', [
            'type' => 'tva',
            'libelle' => 'TVA réduite',
            'taux' => 9,
            'par_defaut' => true,
        ])->assertCreated();

        // L'ancien taux par défaut ne l'est plus
        $this->assertFalse($ancien->fresh()->par_defaut);
    }

    public function test_type_invalide_rejete(): void
    {
        $this->actingAsWithPermissions(['parametres.modifier']);
        $this->postJson('/api/taux-fiscaux', ['type' => 'autre', 'libelle' => 'X', 'taux' => 5])
            ->assertStatus(422)->assertJsonValidationErrors('type');
    }

    public function test_toggle_actif(): void
    {
        $this->actingAsWithPermissions(['parametres.modifier']);
        $taux = TauxFiscalFactory::new()->create(['actif' => true]);

        $this->patchJson("/api/taux-fiscaux/{$taux->id}/toggle")->assertSuccessful();
        $this->assertFalse($taux->fresh()->actif);
    }
}
