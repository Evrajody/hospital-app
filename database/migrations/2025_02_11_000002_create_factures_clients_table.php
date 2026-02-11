<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factures_clients', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20);
            $table->date('date_facture');
            $table->decimal('montant', 15, 2)->default(0);
            $table->unsignedBigInteger('client_id');
            $table->decimal('montant_paye', 15, 2)->default(0);
            $table->decimal('reste_a_payer', 15, 2)->default(0);
            $table->string('statut', 30)->default('non_payee');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('reference');
            $table->index('date_facture');
            $table->index('statut');

            $table->foreign('client_id')
                ->references('id')
                ->on('clients')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factures_clients');
    }
};
