<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute la colonne `date_solde` aux factures (client + fournisseur).
     *
     * Quand une facture est « marquée comme soldée », le statut reste `payee` (comportement
     * inchangé) ; c'est la présence d'une `date_solde` (non nulle) qui distingue une facture
     * marquée soldée manuellement d'une facture réglée par règlement. Aucun nouveau statut,
     * donc aucune contrainte enum à modifier.
     */
    public function up(): void
    {
        Schema::table('factures_fournisseurs', function (Blueprint $table) {
            $table->date('date_solde')->nullable()->after('reste_a_payer');
        });

        Schema::table('factures_clients', function (Blueprint $table) {
            $table->date('date_solde')->nullable()->after('reste_a_payer');
        });
    }

    public function down(): void
    {
        Schema::table('factures_fournisseurs', function (Blueprint $table) {
            $table->dropColumn('date_solde');
        });

        Schema::table('factures_clients', function (Blueprint $table) {
            $table->dropColumn('date_solde');
        });
    }
};
