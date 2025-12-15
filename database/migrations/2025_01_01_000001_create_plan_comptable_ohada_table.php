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
        Schema::create('plan_comptable_ohada', function (Blueprint $table) {
            
            $table->id();

            // Informations du compte
            $table->string('numero_compte', 10)->unique()->index();
            $table->string('libelle', 500);

            // Classification
            $table->integer('classe')->min(1)->max(9)->index();
            $table->integer('niveau')->min(1)->max(5)->index();
            $table->enum('type_compte', ['ACTIF', 'PASSIF', 'CHARGE', 'PRODUIT', 'SPECIAL'])
                  ->index();

            // Utilisation
            $table->boolean('utilisable')->default(true)->index();

            // Timestamps
            $table->timestamps();

            // Contraintes
            // $table->check('classe >= 1 AND classe <= 9');
            // $table->check('niveau >= 1 AND niveau <= 5');

            // Index composites pour les performances
            $table->index(['classe', 'niveau']);
            $table->index(['type_compte', 'classe']);
            $table->index(['utilisable', 'classe']);
        });

        // Index pour les recherches full-text (PostgreSQL)
        DB::statement('CREATE INDEX plan_comptable_ohada_libelle_idx ON plan_comptable_ohada USING gin(to_tsvector(\'french\', libelle))');

        // Commentaires sur la table et les colonnes
        DB::statement("COMMENT ON TABLE plan_comptable_ohada IS 'Plan Comptable OHADA - Organisation pour l''Harmonisation en Afrique du Droit des Affaires'");
        DB::statement("COMMENT ON COLUMN plan_comptable_ohada.numero_compte IS 'Numéro du compte comptable (ex: 601, 6011, etc.)'");
        DB::statement("COMMENT ON COLUMN plan_comptable_ohada.libelle IS 'Libellé explicite du compte'");
        DB::statement("COMMENT ON COLUMN plan_comptable_ohada.classe IS 'Classe du compte (1 à 9): 1=Capitaux, 2=Immobilisations, 3=Stocks, 4=Tiers, 5=Trésorerie, 6=Charges, 7=Produits, 8=H.A.O., 9=Analytique'");
        DB::statement("COMMENT ON COLUMN plan_comptable_ohada.niveau IS 'Niveau hiérarchique du compte (1 à 5): 1=Classe, 2=Compte principal, 3=Sous-compte, etc.'");
        DB::statement("COMMENT ON COLUMN plan_comptable_ohada.type_compte IS 'Type: ACTIF, PASSIF, CHARGE, PRODUIT ou SPECIAL'");
        DB::statement("COMMENT ON COLUMN plan_comptable_ohada.utilisable IS 'Indique si le compte peut être utilisé directement dans les écritures comptables'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_comptable_ohada');
    }
};
