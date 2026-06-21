<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Société émettrice = client de type société (numéro de compte porteur).
        if (! Schema::hasColumn('avances_clients', 'societe_emettrice_client_id')) {
            Schema::table('avances_clients', function (Blueprint $table) {
                $table->unsignedBigInteger('societe_emettrice_client_id')->nullable()->after('client_nom');
                $table->index('societe_emettrice_client_id');
                $table->foreign('societe_emettrice_client_id')
                    ->references('id')->on('clients')->onDelete('set null');
            });
        }

        // 2) Table pivot des bénéficiaires (relation many-to-many).
        if (! Schema::hasTable('avance_client_beneficiaires')) {
            Schema::create('avance_client_beneficiaires', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('avance_id');
                $table->unsignedBigInteger('client_id');
                $table->string('client_nom')->nullable();
                $table->timestamps();

                $table->unique(['avance_id', 'client_id']);
                $table->index('client_id');

                $table->foreign('avance_id')->references('id')->on('avances_clients')->onDelete('cascade');
                $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            });
        }

        // 3) client_id legacy devient nullable (source de vérité = pivot).
        //    On lève d'abord la FK cascade pour la recréer en set null (cohérent avec legacy).
        Schema::table('avances_clients', function (Blueprint $table) {
            try {
                $table->dropForeign(['client_id']);
            } catch (\Throwable $e) {
                // FK déjà absente : on continue.
            }
        });
        Schema::table('avances_clients', function (Blueprint $table) {
            $table->unsignedBigInteger('client_id')->nullable()->change();
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('set null');
        });

        // 4) Backfill : une ligne pivot par avance existante + matching société émettrice.
        DB::table('avances_clients')->whereNull('deleted_at')->orderBy('id')->each(function ($avance) {
            // Bénéficiaire historique → pivot.
            if (! empty($avance->client_id)) {
                $exists = DB::table('avance_client_beneficiaires')
                    ->where('avance_id', $avance->id)
                    ->where('client_id', $avance->client_id)
                    ->exists();
                if (! $exists) {
                    DB::table('avance_client_beneficiaires')->insert([
                        'avance_id' => $avance->id,
                        'client_id' => $avance->client_id,
                        'client_nom' => $avance->client_nom,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Matching best-effort de la société émettrice (string) → client type société.
            if (! empty($avance->societe_emettrice) && empty($avance->societe_emettrice_client_id)) {
                $match = DB::table('clients')
                    ->where('type_client', 'societe')
                    ->whereNull('deleted_at')
                    ->whereRaw('LOWER(TRIM(nom)) = LOWER(TRIM(?))', [$avance->societe_emettrice])
                    ->first();

                if ($match) {
                    DB::table('avances_clients')
                        ->where('id', $avance->id)
                        ->update(['societe_emettrice_client_id' => $match->id]);
                }
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('avance_client_beneficiaires')) {
            Schema::dropIfExists('avance_client_beneficiaires');
        }

        if (Schema::hasColumn('avances_clients', 'societe_emettrice_client_id')) {
            Schema::table('avances_clients', function (Blueprint $table) {
                try {
                    $table->dropForeign(['societe_emettrice_client_id']);
                } catch (\Throwable $e) {
                }
                $table->dropColumn('societe_emettrice_client_id');
            });
        }

        // Restaure client_id NOT NULL + FK cascade (état initial).
        Schema::table('avances_clients', function (Blueprint $table) {
            try {
                $table->dropForeign(['client_id']);
            } catch (\Throwable $e) {
            }
        });
        Schema::table('avances_clients', function (Blueprint $table) {
            $table->unsignedBigInteger('client_id')->nullable(false)->change();
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
        });
    }
};
