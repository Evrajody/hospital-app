<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Factures Fournisseurs — figer le nom du fournisseur + créateur/validateur
        Schema::table('factures_fournisseurs', function (Blueprint $table) {
            $table->string('fournisseur_nom')->nullable()->after('fournisseur_id');
            $table->string('created_by_name')->nullable()->after('created_by');
            $table->string('validated_by_name')->nullable()->after('validated_by');
        });

        // Règlements Fournisseurs — figer le nom du fournisseur, n° facture, créateur, établissement (directeur pour mandats)
        Schema::table('reglements_fournisseurs', function (Blueprint $table) {
            $table->string('fournisseur_nom')->nullable()->after('fournisseur_id');
            $table->string('facture_numero')->nullable()->after('facture_id');
            $table->string('created_by_name')->nullable()->after('created_by');
            $table->string('validated_by_name')->nullable()->after('validated_by');
            $table->string('etablissement_nom')->nullable();
            $table->string('etablissement_directeur')->nullable();
        });

        // Factures Clients — figer le nom du client + créateur
        Schema::table('factures_clients', function (Blueprint $table) {
            $table->string('client_nom')->nullable()->after('client_id');
            $table->string('created_by_name')->nullable()->after('created_by');
        });

        // Règlements Clients — figer le nom du client, ref facture, créateur
        Schema::table('reglements_clients', function (Blueprint $table) {
            $table->string('client_nom')->nullable()->after('client_id');
            $table->string('facture_reference')->nullable()->after('facture_id');
            $table->string('created_by_name')->nullable()->after('created_by');
        });

        // Remplir les snapshots pour les données existantes
        $this->backfillExistingData();
    }

    public function down(): void
    {
        Schema::table('factures_fournisseurs', function (Blueprint $table) {
            $table->dropColumn(['fournisseur_nom', 'created_by_name', 'validated_by_name']);
        });

        Schema::table('reglements_fournisseurs', function (Blueprint $table) {
            $table->dropColumn(['fournisseur_nom', 'facture_numero', 'created_by_name', 'validated_by_name', 'etablissement_nom', 'etablissement_directeur']);
        });

        Schema::table('factures_clients', function (Blueprint $table) {
            $table->dropColumn(['client_nom', 'created_by_name']);
        });

        Schema::table('reglements_clients', function (Blueprint $table) {
            $table->dropColumn(['client_nom', 'facture_reference', 'created_by_name']);
        });
    }

    private function backfillExistingData(): void
    {
        // Factures Fournisseurs
        DB::statement("
            UPDATE factures_fournisseurs SET
                fournisseur_nom = (SELECT nom FROM fournisseurs WHERE fournisseurs.id = factures_fournisseurs.fournisseur_id),
                created_by_name = (SELECT name FROM users WHERE users.id = factures_fournisseurs.created_by),
                validated_by_name = (SELECT name FROM users WHERE users.id = factures_fournisseurs.validated_by)
        ");

        // Règlements Fournisseurs
        DB::statement("
            UPDATE reglements_fournisseurs SET
                fournisseur_nom = (SELECT nom FROM fournisseurs WHERE fournisseurs.id = reglements_fournisseurs.fournisseur_id),
                facture_numero = (SELECT numero_piece FROM factures_fournisseurs WHERE factures_fournisseurs.id = reglements_fournisseurs.facture_id),
                created_by_name = (SELECT name FROM users WHERE users.id = reglements_fournisseurs.created_by),
                validated_by_name = (SELECT name FROM users WHERE users.id = reglements_fournisseurs.validated_by)
        ");

        // Factures Clients
        DB::statement("
            UPDATE factures_clients SET
                client_nom = (SELECT nom FROM clients WHERE clients.id = factures_clients.client_id),
                created_by_name = (SELECT name FROM users WHERE users.id = factures_clients.created_by)
        ");

        // Règlements Clients
        DB::statement("
            UPDATE reglements_clients SET
                client_nom = (SELECT nom FROM clients WHERE clients.id = reglements_clients.client_id),
                facture_reference = (SELECT reference FROM factures_clients WHERE factures_clients.id = reglements_clients.facture_id),
                created_by_name = (SELECT name FROM users WHERE users.id = reglements_clients.created_by)
        ");

        // Établissement sur les règlements fournisseurs existants
        $etablissement = \App\Models\Setting::getEtablissement();
        DB::table('reglements_fournisseurs')->update([
            'etablissement_nom' => $etablissement['nom'],
            'etablissement_directeur' => $etablissement['directeur'],
        ]);
    }
};
