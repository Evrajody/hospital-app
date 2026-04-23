<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('imputations_facture_fournisseur', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facture_id')->constrained('factures_fournisseurs')->cascadeOnDelete();
            $table->foreignId('compte_id')->constrained('plan_comptable_ohada')->cascadeOnDelete();
            $table->decimal('montant', 15, 2)->default(0);
            $table->string('libelle', 500)->nullable();
            $table->timestamps();

            $table->index(['facture_id', 'compte_id']);
        });

        // Migrer les imputations existantes (1 ligne par facture à partir de compte_id)
        $factures = DB::table('factures_fournisseurs')
            ->whereNotNull('compte_id')
            ->whereNull('deleted_at')
            ->get(['id', 'compte_id', 'montant_facture', 'montant_ttc', 'libelle']);

        foreach ($factures as $f) {
            DB::table('imputations_facture_fournisseur')->insert([
                'facture_id' => $f->id,
                'compte_id' => $f->compte_id,
                'montant' => $f->montant_ttc ?: $f->montant_facture,
                'libelle' => $f->libelle,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('imputations_facture_fournisseur');
    }
};
