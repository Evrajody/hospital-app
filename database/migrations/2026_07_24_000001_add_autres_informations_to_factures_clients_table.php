<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('factures_clients', function (Blueprint $table) {
            $table->text('autres_informations')->nullable()->after('ristourne');
        });
    }

    public function down(): void
    {
        Schema::table('factures_clients', function (Blueprint $table) {
            $table->dropColumn('autres_informations');
        });
    }
};
