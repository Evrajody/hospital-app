<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('settings')
            ->where('key', 'etablissement_nom')
            ->whereIn('value', ['Hopital de Menontin', 'Hôpital de Ménontin', 'Hôpital Ménontin'])
            ->update(['value' => 'Hôpital de zone de Ménontin', 'updated_at' => now()]);

        DB::table('settings')->updateOrInsert(
            ['key' => 'etablissement_nom'],
            ['value' => 'Hôpital de zone de Ménontin', 'updated_at' => now(), 'created_at' => now()]
        );

        \Illuminate\Support\Facades\Cache::forget('setting.etablissement_nom');
        \Illuminate\Support\Facades\Cache::forget('settings.etablissement');
    }

    public function down(): void
    {
        // Non-réversible : changement d'appellation officielle.
    }
};
