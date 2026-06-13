<?php

namespace Tests\Unit;

use App\Models\ExportJob;
use Database\Factories\ExportJobFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_id_est_un_uuid(): void
    {
        $job = ExportJobFactory::new()->create();
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $job->id);
    }

    public function test_is_ready_uniquement_si_complete_avec_fichier(): void
    {
        $pending = ExportJobFactory::new()->create();
        $this->assertFalse($pending->isReady());

        $completed = ExportJobFactory::new()->completed()->create();
        $this->assertTrue($completed->isReady());
    }

    public function test_to_api_array_download_url_seulement_si_pret(): void
    {
        $pending = ExportJobFactory::new()->create();
        $this->assertNull($pending->toApiArray()['download_url']);

        $completed = ExportJobFactory::new()->completed()->create();
        $api = $completed->toApiArray();
        $this->assertStringContainsString("/rapports/exports/{$completed->id}/download", $api['download_url']);
        $this->assertSame(100, $api['progress']);
    }

    public function test_params_sont_castes_en_tableau(): void
    {
        $job = ExportJobFactory::new()->create(['params' => ['date_debut' => '2026-01-01']]);
        $this->assertIsArray($job->fresh()->params);
        $this->assertSame('2026-01-01', $job->fresh()->params['date_debut']);
    }
}
