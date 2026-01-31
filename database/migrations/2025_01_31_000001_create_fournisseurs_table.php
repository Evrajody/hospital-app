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
        Schema::create('fournisseurs', function (Blueprint $table) {
            $table->id();

            // Informations générales
            $table->string('nom', 255);
            $table->string('type_fournisseur', 50)->nullable();
            $table->enum('status', ['actif', 'inactif'])->default('actif');

            // Coordonnées
            $table->string('contact', 255)->nullable();
            $table->string('fonction_contact', 100)->nullable();
            $table->string('telephone', 30)->nullable();
            $table->string('telephone_secondaire', 30)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('site_web', 255)->nullable();
            $table->text('adresse')->nullable();
            $table->string('ville', 100)->nullable();
            $table->string('pays', 2)->default('BJ');

            // Compte comptable
            $table->unsignedBigInteger('compte_comptable_id')->nullable();

            // Informations fiscales
            $table->string('ifu', 13)->nullable();
            $table->string('rccm', 50)->nullable();
            $table->string('regime_fiscal', 30)->nullable();
            $table->string('taux_aib', 5)->default('1');
            $table->boolean('assujetti_tva')->default(true);

            // Informations complémentaires
            $table->date('date_debut_relation')->nullable();
            $table->integer('delai_paiement')->default(30);
            $table->string('mode_paiement_prefere', 30)->default('virement');
            $table->decimal('plafond_credit', 15, 2)->nullable();
            $table->text('observations')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Index
            $table->index('nom');
            $table->index('status');
            $table->index('type_fournisseur');
            $table->index('ifu');

            // Foreign key
            $table->foreign('compte_comptable_id')
                ->references('id')
                ->on('plan_comptable_ohada')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fournisseurs');
    }
};
