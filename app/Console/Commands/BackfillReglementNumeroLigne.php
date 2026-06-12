<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Renumérote le n° de ligne des règlements fournisseurs, par facture, dans l'ordre
 * chronologique (1, 2, 3…). Le numéro du règlement = n° pièce facture + n° ligne
 * (ex. PC/026/0017 + 4 → PC/026/00174). Corrige les règlements créés avant l'ajout
 * du n° de ligne (ou avec un format incohérent).
 */
class BackfillReglementNumeroLigne extends Command
{
    protected $signature = 'reglements:numeroter-lignes {--dry-run : Afficher sans modifier}';

    protected $description = 'Renumérote le n° de ligne des règlements fournisseurs par facture (1, 2, 3…).';

    public function handle(): int
    {
        if ($this->option('dry-run')) {
            $apercu = DB::select("
                SELECT facture_id,
                       ROW_NUMBER() OVER (PARTITION BY facture_id ORDER BY date_reglement, id) AS rn,
                       COUNT(*) OVER (PARTITION BY facture_id) AS total
                FROM reglements_fournisseurs
            ");
            $this->info('Aperçu (dry-run) : ' . count($apercu) . ' règlement(s) seraient renumérotés.');
            return self::SUCCESS;
        }

        $count = DB::table('reglements_fournisseurs')->count();

        DB::statement("
            UPDATE reglements_fournisseurs r
            SET numero_ligne = sub.rn::text
            FROM (
                SELECT id, ROW_NUMBER() OVER (PARTITION BY facture_id ORDER BY date_reglement, id) AS rn
                FROM reglements_fournisseurs
            ) sub
            WHERE r.id = sub.id
        ");

        $this->info("N° de ligne recalculés pour {$count} règlement(s) fournisseurs.");
        return self::SUCCESS;
    }
}
