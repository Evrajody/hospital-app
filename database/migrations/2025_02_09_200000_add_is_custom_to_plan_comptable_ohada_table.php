<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('plan_comptable_ohada', function (Blueprint $table) {
            // Champ pour distinguer les comptes personnalisés des comptes OHADA standard
            $table->boolean('is_custom')->default(false)->after('utilisable')->index();

            // Référence au compte parent (pour les comptes personnalisés)
            $table->foreignId('parent_id')->nullable()->after('is_custom')
                  ->constrained('plan_comptable_ohada')->nullOnDelete();

            // Créateur du compte (pour les comptes personnalisés)
            $table->foreignId('created_by')->nullable()->after('parent_id')
                  ->constrained('users')->nullOnDelete();
        });

        // Commentaires
        DB::statement("COMMENT ON COLUMN plan_comptable_ohada.is_custom IS 'true = compte personnalisé, false = compte OHADA standard'");
        DB::statement("COMMENT ON COLUMN plan_comptable_ohada.parent_id IS 'Référence au compte parent pour les comptes personnalisés'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plan_comptable_ohada', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropForeign(['created_by']);
            $table->dropColumn(['is_custom', 'parent_id', 'created_by']);
        });
    }
};
