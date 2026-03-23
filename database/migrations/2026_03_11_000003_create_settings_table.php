<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Valeurs par défaut
        DB::table('settings')->insert([
            ['key' => 'etablissement_nom', 'value' => 'Hopital de Menontin', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'etablissement_pays', 'value' => 'Republique du Benin', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'etablissement_adresse', 'value' => 'BP 123 - Cotonou', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'etablissement_telephone', 'value' => '+229 21 XX XX XX', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'etablissement_email', 'value' => '', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
