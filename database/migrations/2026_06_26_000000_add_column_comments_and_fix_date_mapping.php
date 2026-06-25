<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── PostgreSQL COMMENTS ──────────────────────────────────────
        DB::statement("
            COMMENT ON COLUMN factures_fournisseurs.date
                IS 'Date PC : date d''enregistrement de la facture dans le système (source Access : datenreg)';
        ");
        DB::statement("
            COMMENT ON COLUMN factures_fournisseurs.date_facture_bc
                IS 'Date facture / BC : date inscrite sur la facture par le fournisseur (source Access : datfac)';
        ");
        DB::statement("
            COMMENT ON COLUMN factures_fournisseurs.date_solde
                IS 'Date de règlement : date à laquelle la facture a été soldée (par règlement ou marquage manuel)';
        ");

        // ── FIX DONNÉES EXISTANTES ──────────────────────────────────
        // Le mapping legacy initial avait mal mappé la colonne `date` sur datfac
        // (date facture) au lieu de datenreg (date d'enregistrement / date PC).
        // On corrige : date ← date_facture_bc (qui contient la vraie date facture),
        // car date_facture_bc est correctement mappée sur datfac.
        // Si date_facture_bc est NULL, on laisse la valeur actuelle de `date`.
        DB::statement("
            UPDATE factures_fournisseurs
            SET date = date_facture_bc
            WHERE date_facture_bc IS NOT NULL
              AND date IS DISTINCT FROM date_facture_bc
        ");
    }

    public function down(): void
    {
        DB::statement("COMMENT ON COLUMN factures_fournisseurs.date IS NULL");
        DB::statement("COMMENT ON COLUMN factures_fournisseurs.date_facture_bc IS NULL");
        DB::statement("COMMENT ON COLUMN factures_fournisseurs.date_solde IS NULL");
    }
};
