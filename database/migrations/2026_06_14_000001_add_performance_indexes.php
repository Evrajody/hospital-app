<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Index de performance (PostgreSQL) — anticipation de la montée en volume.
 *
 * - Index btree sur les colonnes de date fréquemment filtrées/triées (rapports,
 *   listes triées du plus récent au plus ancien, filtres par période).
 * - Index GIN « pg_trgm » pour les recherches ILIKE '%terme%' : un index btree
 *   ne peut pas servir un motif à joker initial, le trigram oui → recherche
 *   instantanée même sur des centaines de milliers de lignes.
 *
 * Idempotent (CREATE INDEX IF NOT EXISTS) et propre à PostgreSQL.
 */
return new class extends Migration
{
    /** Index btree : [nom, table, "colonnes"]. */
    private array $btree = [
        ['idx_rf_date_reglement', 'reglements_fournisseurs', '(date_reglement)'],
        ['idx_rf_statut_date',    'reglements_fournisseurs', '(statut, date_reglement)'],
        ['idx_rc_date_reglement', 'reglements_clients',      '(date_reglement)'],
        ['idx_fc_client_date',    'factures_clients',        '(client_id, date_facture)'],
        ['idx_ff_date_facture_bc','factures_fournisseurs',   '(date_facture_bc)'],
        ['idx_ab_compte_date',    'approvisionnements_banques', '(compte_bancaire_id, date_depot)'],
    ];

    /** Index GIN trigram : [nom, table, colonne]. */
    private array $trgm = [
        ['idx_clients_nom_trgm',        'clients',               'nom'],
        ['idx_fournisseurs_nom_trgm',   'fournisseurs',          'nom'],
        ['idx_ff_numero_piece_trgm',    'factures_fournisseurs', 'numero_piece'],
        ['idx_ff_libelle_trgm',         'factures_fournisseurs', 'libelle'],
        ['idx_fcl_reference_trgm',      'factures_clients',      'reference'],
        ['idx_pco_numero_trgm',         'plan_comptable_ohada',  'numero_compte'],
    ];

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        foreach ($this->btree as [$name, $table, $cols]) {
            DB::statement("CREATE INDEX IF NOT EXISTS {$name} ON {$table} {$cols}");
        }

        foreach ($this->trgm as [$name, $table, $col]) {
            DB::statement("CREATE INDEX IF NOT EXISTS {$name} ON {$table} USING gin ({$col} gin_trgm_ops)");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        foreach (array_merge($this->btree, $this->trgm) as $row) {
            DB::statement('DROP INDEX IF EXISTS '.$row[0]);
        }
    }
};
