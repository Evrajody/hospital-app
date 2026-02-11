<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plan_comptable_ohada', function (Blueprint $table) {
            $table->dropIndex(['utilisable', 'classe']);
            $table->dropColumn('utilisable');
        });

        // Rendre type_compte nullable
        Schema::table('plan_comptable_ohada', function (Blueprint $table) {
            $table->string('type_compte')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('plan_comptable_ohada', function (Blueprint $table) {
            $table->boolean('utilisable')->default(true)->after('type_compte');
            $table->index(['utilisable', 'classe']);
        });

        Schema::table('plan_comptable_ohada', function (Blueprint $table) {
            $table->enum('type_compte', ['ACTIF', 'PASSIF', 'CHARGE', 'PRODUIT', 'SPECIAL'])->nullable(false)->change();
        });
    }
};
