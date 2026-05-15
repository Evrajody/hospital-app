<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approvisionnements_banques', function (Blueprint $table) {
            $table->string('reference_bordereau', 100)->nullable()->after('compte_bancaire_id');
            $table->index('reference_bordereau');
        });
    }

    public function down(): void
    {
        Schema::table('approvisionnements_banques', function (Blueprint $table) {
            $table->dropIndex(['reference_bordereau']);
            $table->dropColumn('reference_bordereau');
        });
    }
};
