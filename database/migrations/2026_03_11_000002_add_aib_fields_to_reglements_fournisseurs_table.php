<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reglements_fournisseurs', function (Blueprint $table) {
            $table->boolean('deduire_aib')->default(false)->after('observations');
            $table->decimal('montant_aib_deduit', 15, 2)->default(0)->after('deduire_aib');
            $table->string('compte_aib', 20)->nullable()->after('montant_aib_deduit');
        });
    }

    public function down(): void
    {
        Schema::table('reglements_fournisseurs', function (Blueprint $table) {
            $table->dropColumn(['deduire_aib', 'montant_aib_deduit', 'compte_aib']);
        });
    }
};
