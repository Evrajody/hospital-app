<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nettoyer les doublons éventuels avant d'ajouter les contraintes (paranoia).
        $this->deduplicate('fournisseurs', 'nom');
        $this->deduplicate('banques', 'nom');
        $this->deduplicate('comptes_bancaires', 'numero_compte');

        // Composite unique — garde-fou anti-doublons entre deux runs de la migration.
        $this->deduplicateComposite('clients', ['compte_comptable_id', 'nom']);
        $this->deduplicateComposite('approvisionnements_banques', ['compte_bancaire_id', 'reference_bordereau']);
        $this->deduplicateComposite('reglements_clients', ['facture_id', 'date_reglement', 'montant', 'reference_cheque']);
        $this->deduplicateComposite('imputations_facture_fournisseur', ['facture_id', 'compte_id', 'nature', 'montant']);
        $this->deduplicateComposite('ecritures_comptables', ['facture_id', 'numero_compte', 'debit', 'credit', 'libelle']);

        // --- Contraintes UNIQUE ---
        Schema::table('fournisseurs', fn (Blueprint $t) => $t->unique('nom'));
        Schema::table('clients', fn (Blueprint $t) => $t->unique(['compte_comptable_id', 'nom']));
        Schema::table('banques', fn (Blueprint $t) => $t->unique('nom'));
        Schema::table('comptes_bancaires', fn (Blueprint $t) => $t->unique('numero_compte'));
        Schema::table('approvisionnements_banques', fn (Blueprint $t) => $t->unique(['compte_bancaire_id', 'reference_bordereau']));
        Schema::table('reglements_clients', fn (Blueprint $t) => $t->unique(['facture_id', 'date_reglement', 'montant', 'reference_cheque']));
        Schema::table('imputations_facture_fournisseur', fn (Blueprint $t) => $t->unique(['facture_id', 'compte_id', 'nature', 'montant']));
        Schema::table('ecritures_comptables', fn (Blueprint $t) => $t->unique(['facture_id', 'numero_compte', 'debit', 'credit', 'libelle']));
    }

    public function down(): void
    {
        Schema::table('ecritures_comptables', fn (Blueprint $t) => $t->dropUnique(['facture_id', 'numero_compte', 'debit', 'credit', 'libelle']));
        Schema::table('imputations_facture_fournisseur', fn (Blueprint $t) => $t->dropUnique(['facture_id', 'compte_id', 'nature', 'montant']));
        Schema::table('reglements_clients', fn (Blueprint $t) => $t->dropUnique(['facture_id', 'date_reglement', 'montant', 'reference_cheque']));
        Schema::table('approvisionnements_banques', fn (Blueprint $t) => $t->dropUnique(['compte_bancaire_id', 'reference_bordereau']));
        Schema::table('comptes_bancaires', fn (Blueprint $t) => $t->dropUnique('numero_compte'));
        Schema::table('banques', fn (Blueprint $t) => $t->dropUnique('nom'));
        Schema::table('clients', fn (Blueprint $t) => $t->dropUnique(['compte_comptable_id', 'nom']));
        Schema::table('fournisseurs', fn (Blueprint $t) => $t->dropUnique('nom'));
    }

    /**
     * Supprime les doublons simples (garde la 1ère ligne par ordre d'id).
     */
    private function deduplicate(string $table, string $column): void
    {
        DB::statement("
            DELETE FROM {$table}
            WHERE id NOT IN (
                SELECT MIN(id) FROM {$table} GROUP BY {$column}
            )
        ");
    }

    /**
     * Supprime les doublons sur clé composite (garde la 1ère ligne par ordre d'id).
     */
    private function deduplicateComposite(string $table, array $columns): void
    {
        $cols = implode(', ', $columns);
        DB::statement("
            DELETE FROM {$table}
            WHERE id NOT IN (
                SELECT MIN(id) FROM {$table} GROUP BY {$cols}
            )
        ");
    }
};
