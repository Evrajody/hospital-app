<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('reglements_fournisseurs', function (Blueprint $table) {
            $table->unsignedBigInteger('compte_credit_id')->nullable()->after('compte_tresorerie_id')
                ->comment('Compte fournisseur (401/481) à débiter sur ce règlement, choisi parmi les crédits de la facture');
            $table->foreign('compte_credit_id')->references('id')->on('plan_comptable_ohada')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reglements_fournisseurs', function (Blueprint $table) {
            $table->dropForeign(['compte_credit_id']);
            $table->dropColumn('compte_credit_id');
        });
    }
};
