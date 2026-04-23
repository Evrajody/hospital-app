<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('ifu', 13)->nullable()->after('telephone');
            $table->string('type_client', 20)->default('divers')->after('ifu');
            $table->text('observation')->nullable()->after('type_client');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['ifu', 'type_client', 'observation']);
        });
    }
};
