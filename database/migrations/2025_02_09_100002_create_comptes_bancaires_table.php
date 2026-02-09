<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comptes_bancaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('banque_id')->constrained('banques')->onDelete('cascade');
            $table->string('numero_compte', 50);
            $table->foreignId('compte_ohada_id')
                  ->nullable()
                  ->constrained('plan_comptable_ohada')
                  ->onDelete('set null');
            $table->decimal('solde', 18, 2)->default(0);
            $table->text('observations')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('banque_id');
            $table->index('compte_ohada_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comptes_bancaires');
    }
};
