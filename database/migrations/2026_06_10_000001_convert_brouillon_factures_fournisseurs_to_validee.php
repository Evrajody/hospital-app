<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * L'étape brouillon/validation des factures fournisseurs est supprimée :
     * une facture est active dès sa création. On bascule les factures encore
     * en « brouillon » vers « validee » pour qu'elles soient cohérentes.
     */
    public function up(): void
    {
        DB::table('factures_fournisseurs')
            ->where('statut', 'brouillon')
            ->update(['statut' => 'validee']);
    }

    public function down(): void
    {
        // Irréversible : on ne sait pas distinguer les factures auparavant en brouillon.
    }
};
