<?php

use App\Models\FactureClient;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dénormalise le total des rejets et des pertes sur la facture client afin que
     * le "reste à payer" soit calculé de façon homogène partout :
     *   reste_a_payer = montant - ristourne - (montant_payé + total_rejet + total_perte)
     * (le montant du rejet fait partie du montant du règlement ; cf. point des créances).
     */
    public function up(): void
    {
        Schema::table('factures_clients', function (Blueprint $table) {
            $table->decimal('total_rejet', 15, 2)->default(0)->after('montant_paye');
            $table->decimal('total_perte', 15, 2)->default(0)->after('total_rejet');
        });

        // Backfill : recalcule montant_payé / total_rejet / total_perte / reste / statut
        // à partir des règlements existants, avec la nouvelle règle de calcul.
        FactureClient::query()->with('reglements')->chunkById(200, function ($factures) {
            foreach ($factures as $facture) {
                $facture->recalculerSoldes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('factures_clients', function (Blueprint $table) {
            $table->dropColumn(['total_rejet', 'total_perte']);
        });
    }
};
