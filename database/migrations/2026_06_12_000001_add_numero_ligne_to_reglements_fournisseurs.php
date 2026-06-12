<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * N° de ligne du règlement. Combiné au n° de pièce de la facture, il forme le
     * numéro du règlement (ex. facture PC/026/0017 + ligne 3 → PC/026/00173).
     * Le champ existait dans le formulaire mais n'était pas persisté côté fournisseurs.
     */
    public function up(): void
    {
        Schema::table('reglements_fournisseurs', function (Blueprint $table) {
            $table->string('numero_ligne', 50)->nullable()->after('numero_reglement');
        });

        // Backfill : numéroter les règlements existants par facture (1, 2, 3…) dans
        // l'ordre chronologique, pour que leur numéro composé soit cohérent.
        DB::statement("
            UPDATE reglements_fournisseurs r
            SET numero_ligne = sub.rn::text
            FROM (
                SELECT id, ROW_NUMBER() OVER (PARTITION BY facture_id ORDER BY date_reglement, id) AS rn
                FROM reglements_fournisseurs
            ) sub
            WHERE r.id = sub.id
        ");
    }

    public function down(): void
    {
        Schema::table('reglements_fournisseurs', function (Blueprint $table) {
            $table->dropColumn('numero_ligne');
        });
    }
};
