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
        Schema::create('factures_fournisseurs', function (Blueprint $table) {
            $table->id();

            // Numéro de pièce unique (ex: PC/025/0001)
            $table->string('numero_piece', 50)->unique();

            // Date de la facture
            $table->date('date');

            // Référence externe (N° BC, N° facture fournisseur)
            $table->string('reference_facture', 100)->nullable();

            // Relation avec le fournisseur
            $table->foreignId('fournisseur_id')
                  ->constrained('fournisseurs')
                  ->onDelete('restrict');

            // Imputation comptable (compte de charges 6xxx)
            $table->foreignId('imputation_id')
                  ->nullable()
                  ->constrained('plan_comptable_ohada')
                  ->onDelete('set null');

            // Compte comptable (pour le débit)
            $table->foreignId('compte_id')
                  ->nullable()
                  ->constrained('plan_comptable_ohada')
                  ->onDelete('set null');

            // Libellé de la facture
            $table->string('libelle', 500);

            // Montants
            $table->decimal('montant_facture', 18, 2)->default(0);
            $table->decimal('montant_mo', 18, 2)->default(0)->comment('Montant Main d\'Oeuvre - base calcul taux');
            $table->decimal('avoir', 18, 2)->default(0);

            // Réductions (AIB, Escompte, etc.)
            $table->string('type_reduction', 50)->nullable()->comment('contribution, acomptes, escomptes, aib');
            $table->decimal('taux', 5, 2)->default(0)->comment('Taux en pourcentage');
            $table->decimal('montant_reduction', 18, 2)->default(0)->comment('Montant calculé de la réduction');

            // TVA
            $table->boolean('assujetti_tva')->default(true);
            $table->decimal('taux_tva', 5, 2)->default(18)->comment('Taux TVA applicable');
            $table->decimal('montant_tva', 18, 2)->default(0);

            // Montants calculés
            $table->decimal('montant_ht', 18, 2)->default(0)->comment('Montant hors taxes');
            $table->decimal('montant_ttc', 18, 2)->default(0)->comment('Montant TTC');
            $table->decimal('montant_net', 18, 2)->default(0)->comment('Montant net à payer après réductions');

            // Statut de la facture
            $table->enum('statut', ['brouillon', 'validee', 'partiellement_payee', 'payee', 'annulee'])
                  ->default('brouillon')
                  ->index();

            // Paiements
            $table->decimal('montant_paye', 18, 2)->default(0);
            $table->decimal('reste_a_payer', 18, 2)->default(0);
            $table->date('date_echeance')->nullable();

            // Observations
            $table->text('observations')->nullable();

            // Champs additionnels flexibles (JSON)
            $table->json('metadata')->nullable()->comment('Champs personnalisés additionnels');

            // Traçabilité
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('validated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('validated_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Index pour les recherches fréquentes
            $table->index(['fournisseur_id', 'statut']);
            $table->index(['date', 'statut']);
            $table->index('reference_facture');
        });

        // Commentaires sur la table
        DB::statement("COMMENT ON TABLE factures_fournisseurs IS 'Factures reçues des fournisseurs - Gestion des achats'");
        DB::statement("COMMENT ON COLUMN factures_fournisseurs.numero_piece IS 'Numéro de pièce comptable unique (format: PC/AAA/XXXX)'");
        DB::statement("COMMENT ON COLUMN factures_fournisseurs.montant_mo IS 'Montant de la main d''oeuvre - Base pour le calcul du taux AIB/réduction'");
        DB::statement("COMMENT ON COLUMN factures_fournisseurs.metadata IS 'Champs JSON pour stocker des données additionnelles flexibles'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('factures_fournisseurs');
    }
};
