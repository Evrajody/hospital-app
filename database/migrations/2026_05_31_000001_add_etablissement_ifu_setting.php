<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ajoute la clé IFU (Identifiant Fiscal Unique) de l'établissement si absente
        if (!DB::table('settings')->where('key', 'etablissement_ifu')->exists()) {
            DB::table('settings')->insert([
                'key' => 'etablissement_ifu',
                'value' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'etablissement_ifu')->delete();
    }
};
