<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reglements_clients', function (Blueprint $table) {
            $table->id();
            $table->string('numero_ligne', 50)->nullable();
            $table->date('date_reglement');
            $table->unsignedBigInteger('facture_id');
            $table->unsignedBigInteger('client_id');
            $table->decimal('montant', 15, 2);
            $table->string('institution', 255)->nullable();
            $table->string('reference_cheque', 100)->nullable();
            $table->unsignedBigInteger('banque_depot_id')->nullable();
            $table->unsignedBigInteger('compte_bancaire_id')->nullable();
            $table->text('observations')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['facture_id', 'date_reglement']);
            $table->index('client_id');
            $table->index('institution');

            $table->foreign('facture_id')
                ->references('id')
                ->on('factures_clients')
                ->onDelete('cascade');

            $table->foreign('client_id')
                ->references('id')
                ->on('clients')
                ->onDelete('cascade');

            $table->foreign('banque_depot_id')
                ->references('id')
                ->on('banques')
                ->onDelete('set null');

            $table->foreign('compte_bancaire_id')
                ->references('id')
                ->on('comptes_bancaires')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reglements_clients');
    }
};
