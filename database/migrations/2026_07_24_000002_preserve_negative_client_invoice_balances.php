<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Reconstitue les agrégats et le solde signé depuis les règlements existants.
        // Aucun règlement n'est créé, modifié ou supprimé.
        DB::statement(<<<'SQL'
            WITH reglements_ordonnes AS (
                SELECT
                    r.facture_id,
                    r.date_reglement,
                    r.id,
                    SUM(r.montant + COALESCE(r.montant_rejet, 0)) OVER (
                        PARTITION BY r.facture_id
                        ORDER BY r.date_reglement, r.id
                        ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW
                    ) AS couverture_cumulee
                FROM reglements_clients r
                WHERE r.deleted_at IS NULL
            ),
            dates_solde AS (
                SELECT f.id, MIN(ro.date_reglement) AS date_atteinte_solde
                FROM factures_clients f
                JOIN reglements_ordonnes ro ON ro.facture_id = f.id
                WHERE ro.couverture_cumulee + 0.01 >= f.montant - COALESCE(f.ristourne, 0)
                GROUP BY f.id
            ),
            soldes AS (
                SELECT
                    f.id,
                    COALESCE(SUM(r.montant) FILTER (WHERE r.type_reglement IS DISTINCT FROM 'perte'), 0) AS paye,
                    COALESCE(SUM(r.montant_rejet), 0) AS rejet,
                    COALESCE(SUM(r.montant) FILTER (WHERE r.type_reglement = 'perte'), 0) AS perte,
                    ds.date_atteinte_solde
                FROM factures_clients f
                LEFT JOIN reglements_clients r
                    ON r.facture_id = f.id AND r.deleted_at IS NULL
                LEFT JOIN dates_solde ds ON ds.id = f.id
                WHERE f.deleted_at IS NULL
                GROUP BY f.id, ds.date_atteinte_solde
            )
            UPDATE factures_clients f
            SET montant_paye = s.paye,
                total_rejet = s.rejet,
                total_perte = s.perte,
                reste_a_payer = f.montant - COALESCE(f.ristourne, 0) - s.paye - s.rejet - s.perte,
                statut = CASE
                    WHEN f.statut = 'payee' AND f.date_solde IS NOT NULL THEN 'payee'
                    WHEN f.montant - COALESCE(f.ristourne, 0) - s.paye - s.rejet - s.perte <= 0.01 THEN 'payee'
                    WHEN s.paye + s.rejet + s.perte > 0.01 THEN 'partiellement_payee'
                    ELSE 'non_payee'
                END,
                date_solde = CASE
                    WHEN f.date_solde IS NOT NULL THEN f.date_solde
                    WHEN f.montant - COALESCE(f.ristourne, 0) - s.paye - s.rejet - s.perte <= 0.01 THEN s.date_atteinte_solde
                    ELSE NULL
                END
            FROM soldes s
            WHERE s.id = f.id
        SQL);
    }

    public function down(): void
    {
        // Retour à l'ancien comportement qui masquait les trop-perçus.
        DB::statement(<<<'SQL'
            UPDATE factures_clients
            SET reste_a_payer = GREATEST(reste_a_payer, 0)
            WHERE deleted_at IS NULL
        SQL);
    }
};
