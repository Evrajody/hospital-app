<?php

namespace App\Jobs;

use App\Models\ExportJob;
use App\Support\ReportExportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateReportExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Temps max d'exécution du job (gros PDF/Excel : brouillard sur plusieurs années
     *  → plusieurs milliers de lignes). 600 s était insuffisant → « attempted too many times ». */
    public int $timeout = 1800;

    public int $tries = 1;

    public function __construct(public string $exportJobId) {}

    public function handle(ReportExportService $service): void
    {
        $job = ExportJob::find($this->exportJobId);
        if (! $job || in_array($job->status, [ExportJob::STATUT_COMPLETED, ExportJob::STATUT_CANCELLED], true)) {
            return; // déjà terminé ou annulé avant d'être traité par le worker
        }

        $job->update(['status' => ExportJob::STATUT_PROCESSING, 'progress' => 10, 'step' => 'Initialisation']);

        // Marge mémoire pour les gros rapports (ex. plan comptable ~3300 comptes ≈ 1,3 Go).
        // Le worker absorbe la charge sans impacter la requête web.
        @ini_set('memory_limit', '2048M');

        try {
            $service->generate($job);
        } catch (\Throwable $e) {
            Log::error('Export de rapport échoué', [
                'export_job_id' => $job->id,
                'report' => $job->report,
                'format' => $job->format,
                'message' => $e->getMessage(),
            ]);
            $job->update([
                'status' => ExportJob::STATUT_FAILED,
                'error' => $e->getMessage(),
                'completed_at' => now(),
            ]);
        }
    }

    public function failed(\Throwable $e): void
    {
        $job = ExportJob::find($this->exportJobId);
        if ($job && $job->status !== ExportJob::STATUT_COMPLETED) {
            $job->update([
                'status' => ExportJob::STATUT_FAILED,
                'error' => $e->getMessage(),
                'completed_at' => now(),
            ]);
        }
    }
}
