<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Date de la référence de paiement (date du chèque / de l'ordre de virement),
     * distincte de la date de règlement. Elle était saisie dans le formulaire mais
     * n'était jamais persistée.
     */
    public function up(): void
    {
        Schema::table('reglements_fournisseurs', function (Blueprint $table) {
            $table->date('date_reference')->nullable()->after('reference');
        });
    }

    public function down(): void
    {
        Schema::table('reglements_fournisseurs', function (Blueprint $table) {
            $table->dropColumn('date_reference');
        });
    }
};
