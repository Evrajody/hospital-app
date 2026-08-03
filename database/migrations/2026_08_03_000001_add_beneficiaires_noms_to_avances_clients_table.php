<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('avances_clients', function (Blueprint $table) {
            $table->json('beneficiaires_noms')->nullable()->after('societe_emettrice');
        });

        DB::table('avances_clients')->orderBy('id')->chunkById(100, function ($avances) {
            foreach ($avances as $avance) {
                $noms = DB::table('avance_client_beneficiaires as acb')
                    ->leftJoin('clients as c', 'c.id', '=', 'acb.client_id')
                    ->where('acb.avance_id', $avance->id)
                    ->orderBy('acb.id')
                    ->pluck(DB::raw('COALESCE(acb.client_nom, c.nom)'))
                    ->filter()
                    ->values()
                    ->all();

                if ($noms === [] && $avance->client_nom) {
                    $noms = [$avance->client_nom];
                }

                DB::table('avances_clients')->where('id', $avance->id)->update([
                    'beneficiaires_noms' => json_encode($noms, JSON_UNESCAPED_UNICODE),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('avances_clients', function (Blueprint $table) {
            $table->dropColumn('beneficiaires_noms');
        });
    }
};
