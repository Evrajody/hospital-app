<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('factures_fournisseurs', function (Blueprint $table) {
            // Supprimer l'ancienne FK vers plan_comptable_ohada
            $table->dropForeign(['imputation_id']);
            $table->dropColumn('imputation_id');
        });

        Schema::table('factures_fournisseurs', function (Blueprint $table) {
            // Recreer imputation_id comme FK vers classes
            $table->foreignId('imputation_id')
                  ->nullable()
                  ->after('fournisseur_id')
                  ->constrained('classes')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('factures_fournisseurs', function (Blueprint $table) {
            $table->dropForeign(['imputation_id']);
            $table->dropColumn('imputation_id');
        });

        Schema::table('factures_fournisseurs', function (Blueprint $table) {
            $table->foreignId('imputation_id')
                  ->nullable()
                  ->after('fournisseur_id')
                  ->constrained('plan_comptable_ohada')
                  ->onDelete('set null');
        });
    }
};
