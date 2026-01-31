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
        Schema::create('reglements_fournisseurs', function (Blueprint $table) {
            $table->id();

            // Numéro de référence unique du règlement
            $table->string('numero_reglement', 50)->unique();

            // Date du règlement
            $table->date('date_reglement');

            // Relation avec la facture
            $table->foreignId('facture_id')
                  ->constrained('factures_fournisseurs')
                  ->onDelete('restrict');

            // Relation avec le fournisseur (dénormalisé pour faciliter les requêtes)
            $table->foreignId('fournisseur_id')
                  ->constrained('fournisseurs')
                  ->onDelete('restrict');

            // Montant du règlement
            $table->decimal('montant', 18, 2);

            // Mode de paiement
            $table->enum('mode_paiement', ['virement', 'cheque', 'especes', 'mobile_money', 'carte'])
                  ->default('virement');

            // Référence du paiement (n° chèque, n° virement, etc.)
            $table->string('reference', 100)->nullable();

            // Informations bancaires
            $table->string('banque', 100)->nullable();
            $table->string('numero_compte_bancaire', 50)->nullable();

            // Compte comptable de trésorerie utilisé (52xxx ou 57xxx)
            $table->foreignId('compte_tresorerie_id')
                  ->nullable()
                  ->constrained('plan_comptable_ohada')
                  ->onDelete('set null');

            // Observations
            $table->text('observations')->nullable();

            // Statut du règlement
            $table->enum('statut', ['en_attente', 'valide', 'annule'])
                  ->default('valide');

            // Traçabilité
            $table->foreignId('created_by')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');

            $table->foreignId('validated_by')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');

            $table->timestamp('validated_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Index pour les recherches fréquentes
            $table->index(['facture_id', 'statut']);
            $table->index(['fournisseur_id', 'date_reglement']);
            $table->index('mode_paiement');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reglements_fournisseurs');
    }
};
