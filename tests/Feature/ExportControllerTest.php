<?php

namespace Tests\Feature;

use App\Jobs\GenerateReportExport;
use App\Models\ExportJob;
use Database\Factories\ExportJobFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ExportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_demarrer_un_export_cree_le_job_et_le_met_en_file(): void
    {
        Queue::fake();
        $user = UserFactory::new()->create();

        $this->actingAs($user)
            ->postJson('/rapports/exports', [
                'report' => 'rapports-clients.brouillard-cheques',
                'format' => 'pdf',
                'params' => ['date_debut' => '2026-01-01', 'date_fin' => '2026-12-31'],
            ])
            ->assertSuccessful()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('export_jobs', [
            'user_id' => $user->id,
            'report' => 'rapports-clients.brouillard-cheques',
            'format' => 'pdf',
            'status' => ExportJob::STATUT_PENDING,
        ]);

        Queue::assertPushed(GenerateReportExport::class);
    }

    public function test_format_non_supporte_est_rejete(): void
    {
        $this->actingAs(UserFactory::new()->create());

        $this->postJson('/rapports/exports', [
            'report' => 'rapports-clients.brouillard-cheques',
            'format' => 'xml',
        ])->assertStatus(422);
    }

    public function test_rapport_inconnu_est_rejete(): void
    {
        Queue::fake();
        $this->actingAs(UserFactory::new()->create());

        $this->postJson('/rapports/exports', [
            'report' => 'rapport.inexistant',
            'format' => 'pdf',
        ])->assertStatus(422);

        Queue::assertNothingPushed();
    }

    public function test_telechargement_interdit_pour_un_autre_utilisateur(): void
    {
        $proprietaire = UserFactory::new()->create();
        $job = ExportJobFactory::new()->completed()->create(['user_id' => $proprietaire->id]);

        $this->actingAs(UserFactory::new()->create())
            ->get("/rapports/exports/{$job->id}/download")
            ->assertStatus(403);
    }

    public function test_telechargement_404_si_fichier_absent(): void
    {
        $user = UserFactory::new()->create();
        // Job marqué complété mais le fichier n'existe pas réellement sur le disque.
        $job = ExportJobFactory::new()->completed()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get("/rapports/exports/{$job->id}/download")
            ->assertStatus(404);
    }

    public function test_status_renvoie_l_etat_du_job(): void
    {
        $user = UserFactory::new()->create();
        $job = ExportJobFactory::new()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->getJson("/rapports/exports/{$job->id}/status")
            ->assertSuccessful()
            ->assertJsonPath('export.id', $job->id);
    }
}
