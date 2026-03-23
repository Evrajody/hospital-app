<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action', 50); // create, update, delete, validate, cancel, etc.
            $table->string('module', 50); // facture_fournisseur, reglement_client, etc.
            $table->string('description');
            $table->string('subject_type')->nullable(); // App\Models\FactureFournisseur
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('properties')->nullable(); // old/new values, metadata
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['module', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
