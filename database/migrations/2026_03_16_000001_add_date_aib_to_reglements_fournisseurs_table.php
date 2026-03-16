<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reglements_fournisseurs', function (Blueprint $table) {
            $table->date('date_aib')->nullable()->after('compte_aib');
        });
    }

    public function down(): void
    {
        Schema::table('reglements_fournisseurs', function (Blueprint $table) {
            $table->dropColumn('date_aib');
        });
    }
};
