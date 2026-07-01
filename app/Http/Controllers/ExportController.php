<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateReportExport;
use App\Models\ExportJob;
use App\Support\ReportExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    /**
     * Crée une demande d'export asynchrone et la met en file d'attente.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'report' => ['required', 'string'],
            'format' => ['required', 'in:pdf,excel'],
            'params' => ['nullable', 'array'],
        ]);

        if (! ReportExportService::supports($data['report'], $data['format'])) {
            return response()->json([
                'success' => false,
                'message' => 'Rapport ou format non supporté.',
            ], 422);
        }

        // Purge des exports de plus de 24h (évite l'accumulation des fichiers générés).
        ExportJob::where('created_at', '<', now()->subDay())->get()->each(function (ExportJob $old) {
            if ($old->file_path) {
                Storage::disk('local')->delete($old->file_path);
            }
            $old->delete();
        });

        $job = ExportJob::create([
            'user_id' => auth()->id(),
            'report' => $data['report'],
            'format' => $data['format'],
            'label' => ReportExportService::label($data['report']).' ('.strtoupper($data['format']).')',
            'params' => $data['params'] ?? [],
            'status' => ExportJob::STATUT_PENDING,
        ]);

        GenerateReportExport::dispatch($job->id);

        return response()->json([
            'success' => true,
            'export' => $job->toApiArray(),
        ]);
    }

    /**
     * Statut d'une demande d'export (pour le polling côté front).
     */
    public function status(string $id): JsonResponse
    {
        $job = ExportJob::findOrFail($id);
        abort_unless($job->user_id === auth()->id(), 403);

        return response()->json(['export' => $job->toApiArray()]);
    }

    /**
     * Annuler un export en cours (ou en file d'attente).
     *
     * Marque le job « cancelled » ; le job/service s'interrompt avant le rendu (ou
     * jette le résultat si le rendu était déjà en cours). Idempotent.
     */
    public function cancel(string $id): JsonResponse
    {
        $job = ExportJob::findOrFail($id);
        abort_unless($job->user_id === auth()->id(), 403);

        if (in_array($job->status, [ExportJob::STATUT_PENDING, ExportJob::STATUT_PROCESSING], true)) {
            $job->update([
                'status' => ExportJob::STATUT_CANCELLED,
                'step' => 'Annulé',
                'completed_at' => now(),
            ]);
        }

        return response()->json(['success' => true, 'export' => $job->fresh()->toApiArray()]);
    }

    /**
     * Téléchargement du fichier généré.
     */
    public function download(string $id): StreamedResponse
    {
        $job = ExportJob::findOrFail($id);
        abort_unless($job->user_id === auth()->id(), 403);
        abort_unless($job->isReady() && Storage::disk('local')->exists($job->file_path), 404);

        return Storage::disk('local')->download($job->file_path, $job->file_name);
    }
}
