<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Certains bordereaux hérités (données legacy) n'ont pas de date de dépôt.
     * On rend date_depot nullable pour ne pas perdre ces enregistrements ni
     * inventer une date à l'import.
     */
    public function up(): void
    {
        Schema::table('approvisionnements_banques', function (Blueprint $table) {
            $table->date('date_depot')->nullable()->change();
        });
    }

    public function down(): void
    {
        // On ne reforce pas NOT NULL (des lignes peuvent désormais contenir NULL).
        Schema::table('approvisionnements_banques', function (Blueprint $table) {
            $table->date('date_depot')->nullable()->change();
        });
    }
};
