<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reglement_fournisseur_lignes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reglement_id')->constrained('reglements_fournisseurs')->cascadeOnDelete();
            $table->unsignedBigInteger('compte_id'); // FK vers plan_comptable_ohada
            $table->decimal('montant', 15, 2)->default(0);
            $table->string('libelle', 500)->nullable();
            $table->timestamps();

            $table->foreign('compte_id')->references('id')->on('plan_comptable_ohada')->cascadeOnDelete();
            $table->index(['reglement_id', 'compte_id']);
        });

        // Backfill : pour les règlements existants avec compte_credit_id, créer une ligne unique
        // Le montant de la ligne = montant du règlement + AIB déduit (= total soldé sur le compte)
        DB::statement("
            INSERT INTO reglement_fournisseur_lignes (reglement_id, compte_id, montant, libelle, created_at, updated_at)
            SELECT id, compte_credit_id,
                   (montant + COALESCE(montant_aib_deduit, 0)),
                   reference,
                   NOW(), NOW()
            FROM reglements_fournisseurs
            WHERE compte_credit_id IS NOT NULL
              AND statut != 'annule'
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('reglement_fournisseur_lignes');
    }
};
