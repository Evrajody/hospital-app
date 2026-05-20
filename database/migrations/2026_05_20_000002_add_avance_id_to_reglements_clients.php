<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reglements_clients', function (Blueprint $table) {
            $table->unsignedBigInteger('avance_id')->nullable()->after('approvisionnement_id');

            $table->foreign('avance_id')->references('id')->on('avances_clients')->onDelete('restrict');
            $table->index('avance_id');
        });
    }

    public function down(): void
    {
        Schema::table('reglements_clients', function (Blueprint $table) {
            $table->dropForeign(['avance_id']);
            $table->dropIndex(['avance_id']);
            $table->dropColumn('avance_id');
        });
    }
};
