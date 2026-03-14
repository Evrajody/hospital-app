<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecritures_comptables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facture_id')->constrained('factures_fournisseurs')->cascadeOnDelete();
            $table->foreignId('reglement_id')->nullable()->constrained('reglements_fournisseurs')->nullOnDelete();
            $table->date('date_ecriture');
            $table->string('numero_compte', 20);
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->string('libelle', 500)->nullable();
            $table->string('type', 20); // 'facture' ou 'reglement'
            $table->timestamps();

            $table->index(['facture_id', 'date_ecriture']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecritures_comptables');
    }
};
