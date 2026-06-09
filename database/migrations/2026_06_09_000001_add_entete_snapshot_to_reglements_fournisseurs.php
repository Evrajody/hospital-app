<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Figer le bloc d'en-tête officiel (coin haut-droit des bordereaux) sur le
        // règlement, comme etablissement_nom / etablissement_directeur. Ainsi une
        // modification ultérieure des paramètres n'affecte que les nouveaux bordereaux.
        Schema::table('reglements_fournisseurs', function (Blueprint $table) {
            $table->string('etablissement_entete_bp')->nullable();
            $table->string('etablissement_entete_tel')->nullable();
            $table->string('etablissement_entete_email')->nullable();
            $table->string('etablissement_entete_site')->nullable();
        });

        // Backfill : figer les règlements existants sur les valeurs d'en-tête actuelles.
        $etablissement = \App\Models\Setting::getEtablissement();
        DB::table('reglements_fournisseurs')->update([
            'etablissement_entete_bp' => $etablissement['entete_bp'],
            'etablissement_entete_tel' => $etablissement['entete_tel'],
            'etablissement_entete_email' => $etablissement['entete_email'],
            'etablissement_entete_site' => $etablissement['entete_site'],
        ]);
    }

    public function down(): void
    {
        Schema::table('reglements_fournisseurs', function (Blueprint $table) {
            $table->dropColumn([
                'etablissement_entete_bp',
                'etablissement_entete_tel',
                'etablissement_entete_email',
                'etablissement_entete_site',
            ]);
        });
    }
};
