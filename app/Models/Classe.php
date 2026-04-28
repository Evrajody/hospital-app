<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classe extends Model
{
    protected $table = 'classes';

    protected $fillable = [
        'code',
        'libelle',
        'prefixe_compte',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Retourne les comptes comptables correspondant au prefixe de cette classe.
     */
    public function comptes()
    {
        return CompteComptable::where('numero_compte', 'LIKE', $this->prefixe_compte . '%')
            ->whereRaw('LENGTH(numero_compte) >= 2')
            ->orderBy('numero_compte');
    }

    /**
     * Factures fournisseurs imputees a cette classe.
     */
    public function factures(): HasMany
    {
        return $this->hasMany(FactureFournisseur::class, 'imputation_id');
    }

    /**
     * Scope: classes actives uniquement.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Retourne les imputations sélectionnables par l'utilisateur sur une facture :
     * - Classe 2 (Immobilisations)
     * - Compte 42 (Personnel)
     * - Classe 6 (Charges)
     * - Compte 401 (Fournisseurs - exploitation/achats)
     * - Compte 481 (Fournisseurs d'investissements)
     *
     * NB : la TVA (445) et l'AIB ne sont PAS dans cette liste — elles sont
     * auto-générées dans les écritures comptables (TVA si assujettie, AIB si déduit).
     * Crées automatiquement si manquants.
     */
    public static function imputationsFactureFournisseur()
    {
        $required = [
            ['code' => '2',    'libelle' => 'Immobilisations',                'prefixe_compte' => '2'],
            ['code' => '42',   'libelle' => 'Personnel',                      'prefixe_compte' => '42'],
            ['code' => '6',    'libelle' => 'Charges',                        'prefixe_compte' => '6'],
            ['code' => '401',  'libelle' => 'Fournisseurs (exploitation)',    'prefixe_compte' => '401'],
            ['code' => '481',  'libelle' => 'Fournisseurs d\'investissements','prefixe_compte' => '481'],
        ];

        foreach ($required as $entry) {
            static::firstOrCreate(
                ['code' => $entry['code']],
                array_merge($entry, ['is_active' => true])
            );
        }

        return static::active()
            ->whereIn('code', array_column($required, 'code'))
            ->orderByRaw("CASE code
                WHEN '2' THEN 1
                WHEN '42' THEN 2
                WHEN '6' THEN 3
                WHEN '401' THEN 4
                WHEN '481' THEN 5
                ELSE 99 END")
            ->get();
    }
}
