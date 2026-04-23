<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fournisseur_comptes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fournisseur_id')->constrained('fournisseurs')->cascadeOnDelete();
            $table->foreignId('compte_comptable_id')->constrained('plan_comptable_ohada')->cascadeOnDelete();
            $table->boolean('principal')->default(false);
            $table->timestamps();

            $table->unique(['fournisseur_id', 'compte_comptable_id']);
            $table->index('fournisseur_id');
        });

        // Synchroniser les comptes existants (compte_comptable_id) dans la nouvelle table comme principal
        $rows = DB::table('fournisseurs')
            ->whereNotNull('compte_comptable_id')
            ->whereNull('deleted_at')
            ->get(['id', 'compte_comptable_id']);

        foreach ($rows as $row) {
            DB::table('fournisseur_comptes')->insert([
                'fournisseur_id' => $row->id,
                'compte_comptable_id' => $row->compte_comptable_id,
                'principal' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fournisseur_comptes');
    }
};
