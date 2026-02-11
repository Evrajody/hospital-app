<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 255);
            $table->string('telephone', 30)->nullable();
            $table->text('adresse')->nullable();
            $table->unsignedBigInteger('compte_comptable_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('nom');

            $table->foreign('compte_comptable_id')
                ->references('id')
                ->on('plan_comptable_ohada')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
