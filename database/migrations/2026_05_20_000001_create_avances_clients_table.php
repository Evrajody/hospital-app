<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avances_clients', function (Blueprint $table) {
            $table->id();
            $table->string('numero_ligne', 50)->nullable();
            $table->unsignedBigInteger('client_id');
            $table->string('client_nom')->nullable();
            $table->string('societe_emettrice');
            $table->string('numero_cheque', 100);
            $table->date('date_cheque');
            $table->decimal('montant', 15, 2);
            $table->decimal('montant_utilise', 15, 2)->default(0);
            $table->string('numero_proforma', 100)->nullable();
            $table->enum('statut', ['disponible', 'partiellement_utilisee', 'epuisee'])->default('disponible');

            // Dépôt bancaire du chèque (optionnel, mais cohérent avec les règlements)
            $table->string('institution', 255)->nullable();
            $table->unsignedBigInteger('banque_depot_id')->nullable();
            $table->unsignedBigInteger('approvisionnement_id')->nullable();
            $table->string('bordereau_depot_path')->nullable();

            $table->text('observations')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('created_by_name')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('client_id');
            $table->index('societe_emettrice');
            $table->index('statut');
            $table->index('date_cheque');

            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('banque_depot_id')->references('id')->on('banques')->onDelete('set null');
            $table->foreign('approvisionnement_id')->references('id')->on('approvisionnements_banques')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avances_clients');
    }
};
