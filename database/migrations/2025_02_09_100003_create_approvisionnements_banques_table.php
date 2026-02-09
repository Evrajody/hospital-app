<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approvisionnements_banques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compte_bancaire_id')->constrained('comptes_bancaires')->onDelete('cascade');
            $table->date('date_depot');
            $table->decimal('montant', 18, 2);
            $table->text('observations')->nullable();
            $table->string('piece_jointe')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index('compte_bancaire_id');
            $table->index('date_depot');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approvisionnements_banques');
    }
};
