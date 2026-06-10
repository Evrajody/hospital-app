<?php

namespace App\Support;

use App\Http\Controllers\PlanComptableController;
use App\Http\Controllers\RapportClientController;
use App\Http\Controllers\RapportFournisseurController;
use App\Models\ExportJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Génère les exports de rapports (PDF / Excel) de manière asynchrone.
 *
 * Au lieu de dupliquer la logique, on réutilise les méthodes d'export déjà
 * présentes dans les contrôleurs : on les invoque avec une Request synthétique
 * (construite depuis les filtres enregistrés), puis on capture le contenu de la
 * réponse pour l'écrire sur le disque. Le téléchargement se fait ensuite via
 * ExportController::download().
 */
class ReportExportService
{
    /**
     * Registre : clé de rapport => contrôleur + méthodes (pdf/excel) + libellé.
     */
    public const REPORTS = [
        'rapports-fournisseurs.situation-fournisseurs' => [
            'controller' => RapportFournisseurController::class,
            'pdf' => 'situationFournisseursPdf',
            'excel' => 'situationFournisseursExcel',
            'label' => 'Situation des fournisseurs',
        ],
        'rapports-clients.etat-reglements' => [
            'controller' => RapportClientController::class,
            'pdf' => 'etatReglementsPdf',
            'excel' => 'etatReglementsExcel',
            'label' => 'État des règlements clients',
        ],
        'rapports-clients.etat-creances' => [
            'controller' => RapportClientController::class,
            'pdf' => 'etatCreancesPdf',
            'excel' => 'etatCreancesExcel',
            'label' => 'État des créances clients',
        ],
        'rapports-clients.brouillard-cheques' => [
            'controller' => RapportClientController::class,
            'pdf' => 'brouillardChequesPdf',
            'excel' => 'brouillardChequesExcel',
            'label' => 'Brouillard des chèques',
        ],
        'rapports-clients.pertes-rejets' => [
            'controller' => RapportClientController::class,
            'pdf' => 'pertesRejetsPdf',
            'excel' => 'pertesRejetsExcel',
            'label' => 'Pertes et rejets',
        ],
        'plan-comptable' => [
            'controller' => PlanComptableController::class,
            'pdf' => 'exportPdf',
            'excel' => 'exportExcel',
            'label' => 'Plan comptable OHADA',
        ],
    ];

    public static function supports(string $report, string $format): bool
    {
        return isset(self::REPORTS[$report]) && isset(self::REPORTS[$report][$format]);
    }

    public static function label(string $report): string
    {
        return self::REPORTS[$report]['label'] ?? $report;
    }

    /**
     * Génère le fichier d'export et met à jour l'ExportJob (statut, chemin, nom).
     */
    public function generate(ExportJob $job): void
    {
        $def = self::REPORTS[$job->report] ?? null;
        if (! $def) {
            throw new \InvalidArgumentException("Rapport inconnu : {$job->report}");
        }
        $method = $def[$job->format] ?? null;
        if (! $method) {
            throw new \InvalidArgumentException("Format non supporté : {$job->format}");
        }

        // Request synthétique reconstruite depuis les filtres du rapport.
        $params = $job->params ?? [];
        unset($params['action']); // on force le téléchargement, pas le stream
        $request = Request::create('/exports/run', 'GET', $params);
        app()->instance('request', $request);

        // Contexte utilisateur (libellés « généré par », etc.).
        if ($job->user) {
            Auth::setUser($job->user);
        }

        $controller = app($def['controller']);
        $response = app()->call([$controller, $method], ['request' => $request]);

        $content = $this->captureContent($response);
        if ($content === '') {
            throw new \RuntimeException('Contenu de l\'export vide.');
        }

        $ext = $job->format === 'excel' ? 'xlsx' : 'pdf';
        $slug = str_replace(['/', '.'], '-', $job->report);
        $fileName = "{$slug}-".now()->format('Ymd-His').".{$ext}";
        $path = "exports/{$job->id}.{$ext}";

        Storage::disk('local')->put($path, $content);

        $job->update([
            'status' => ExportJob::STATUT_COMPLETED,
            'file_path' => $path,
            'file_name' => $fileName,
            'completed_at' => now(),
            'error' => null,
        ]);
    }

    /**
     * Récupère le contenu binaire d'une réponse (Response classique ou StreamedResponse).
     */
    private function captureContent($response): string
    {
        if ($response instanceof StreamedResponse) {
            ob_start();
            $response->sendContent();
            return (string) ob_get_clean();
        }

        $content = method_exists($response, 'getContent') ? $response->getContent() : false;
        if ($content === false || $content === null || $content === '') {
            ob_start();
            $response->sendContent();
            return (string) ob_get_clean();
        }
        return (string) $content;
    }
}
