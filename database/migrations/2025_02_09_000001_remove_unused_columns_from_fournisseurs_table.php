<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fournisseurs', function (Blueprint $table) {
            // Supprimer l'index sur status avant de supprimer la colonne
            $table->dropIndex(['status']);

            // Supprimer les colonnes inutilisées
            $table->dropColumn([
                'status',
                'regime_fiscal',
                'taux_aib',
                'assujetti_tva',
                'date_debut_relation',
                'delai_paiement',
                'mode_paiement_prefere',
                'plafond_credit',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fournisseurs', function (Blueprint $table) {
            // Recréer les colonnes
            $table->enum('status', ['actif', 'inactif'])->default('actif')->after('type_fournisseur');
            $table->string('regime_fiscal', 30)->nullable()->after('rccm');
            $table->string('taux_aib', 5)->default('1')->after('regime_fiscal');
            $table->boolean('assujetti_tva')->default(true)->after('taux_aib');
            $table->date('date_debut_relation')->nullable()->after('assujetti_tva');
            $table->integer('delai_paiement')->default(30)->after('date_debut_relation');
            $table->string('mode_paiement_prefere', 30)->default('virement')->after('delai_paiement');
            $table->decimal('plafond_credit', 15, 2)->nullable()->after('mode_paiement_prefere');

            // Recréer l'index
            $table->index('status');
        });
    }
};
