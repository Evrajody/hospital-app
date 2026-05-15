<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reglements_clients', function (Blueprint $table) {
            $table->dropForeign(['compte_bancaire_id']);
            $table->dropColumn('compte_bancaire_id');

            $table->unsignedBigInteger('approvisionnement_id')->nullable()->after('banque_depot_id');
            $table->foreign('approvisionnement_id')
                ->references('id')
                ->on('approvisionnements_banques')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('reglements_clients', function (Blueprint $table) {
            $table->dropForeign(['approvisionnement_id']);
            $table->dropColumn('approvisionnement_id');

            $table->unsignedBigInteger('compte_bancaire_id')->nullable()->after('banque_depot_id');
            $table->foreign('compte_bancaire_id')
                ->references('id')
                ->on('comptes_bancaires')
                ->onDelete('set null');
        });
    }
};
