<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taux_fiscaux', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->comment('tva ou aib');
            $table->string('libelle', 100);
            $table->decimal('taux', 5, 2);
            $table->boolean('actif')->default(true);
            $table->boolean('par_defaut')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('type');
            $table->index('actif');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taux_fiscaux');
    }
};
