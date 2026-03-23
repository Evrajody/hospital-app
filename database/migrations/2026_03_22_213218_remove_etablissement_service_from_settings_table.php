<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('settings')->where('key', 'etablissement_service')->delete();
    }

    public function down(): void
    {
        DB::table('settings')->insert([
            'key' => 'etablissement_service',
            'value' => 'Service Comptabilite',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
