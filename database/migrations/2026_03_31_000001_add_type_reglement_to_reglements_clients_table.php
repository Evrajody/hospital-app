<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reglements_clients', function (Blueprint $table) {
            $table->string('type_reglement', 30)->default('reglement')->after('numero_ligne');
            $table->index('type_reglement');
        });
    }

    public function down(): void
    {
        Schema::table('reglements_clients', function (Blueprint $table) {
            $table->dropIndex(['type_reglement']);
            $table->dropColumn('type_reglement');
        });
    }
};
