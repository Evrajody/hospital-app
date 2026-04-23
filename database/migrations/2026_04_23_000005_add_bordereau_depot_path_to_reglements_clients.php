<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('reglements_clients', function (Blueprint $table) {
            $table->string('bordereau_depot_path', 500)->nullable()->after('observations');
        });
    }

    public function down(): void
    {
        Schema::table('reglements_clients', function (Blueprint $table) {
            $table->dropColumn('bordereau_depot_path');
        });
    }
};
