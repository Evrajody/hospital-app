<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('imputations_facture_fournisseur', function (Blueprint $table) {
            $table->string('nature', 10)->default('debit')->after('compte_id')
                ->comment('debit ou credit (charges/immo/personnel = debit ; fournisseurs 401/481 = credit)');
        });

        // Backfill : déduire la nature à partir du préfixe du compte
        DB::statement("
            UPDATE imputations_facture_fournisseur AS ifu
            SET nature = CASE
                WHEN pco.numero_compte LIKE '401%' OR pco.numero_compte LIKE '481%' THEN 'credit'
                ELSE 'debit'
            END
            FROM plan_comptable_ohada AS pco
            WHERE ifu.compte_id = pco.id
        ");
    }

    public function down(): void
    {
        Schema::table('imputations_facture_fournisseur', function (Blueprint $table) {
            $table->dropColumn('nature');
        });
    }
};
