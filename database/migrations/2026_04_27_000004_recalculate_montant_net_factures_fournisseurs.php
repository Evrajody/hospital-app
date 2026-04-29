<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Recalculer montant_net (NAP) pour toutes les factures :
        // Modèle "TVA pour compte" : NAP = HT − avoir − AIB
        // (la TVA n'est pas remise au fournisseur, elle reste à l'État)
        DB::statement("
            UPDATE factures_fournisseurs
            SET
                montant_net = COALESCE(montant_facture, 0)
                              - COALESCE(avoir, 0)
                              - COALESCE(montant_reduction, 0),
                reste_a_payer = (COALESCE(montant_facture, 0)
                                 - COALESCE(avoir, 0)
                                 - COALESCE(montant_reduction, 0))
                                - COALESCE(montant_paye, 0)
            WHERE deleted_at IS NULL
        ");

        // Recalculer les statuts en conséquence
        DB::statement("
            UPDATE factures_fournisseurs
            SET statut = CASE
                WHEN reste_a_payer <= 0.01 AND montant_paye > 0 THEN 'payee'
                WHEN montant_paye > 0 AND reste_a_payer > 0.01 THEN 'partiellement_payee'
                WHEN montant_paye <= 0.01 AND statut IN ('payee', 'partiellement_payee') THEN 'validee'
                ELSE statut
            END
            WHERE deleted_at IS NULL
              AND statut NOT IN ('annulee', 'soldee', 'brouillon')
        ");
    }

    public function down(): void
    {
        // Pas de rollback automatique
    }
};
