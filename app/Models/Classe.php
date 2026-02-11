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
     * Retourne les imputations pour les factures fournisseurs :
     * Classe 2 (Immobilisations), Classe 6 (Charges) et Compte 42 (Personnel).
     * Le compte 42 est cree automatiquement s'il n'existe pas.
     */
    public static function imputationsFactureFournisseur()
    {
        // S'assurer que le compte 42 existe
        static::firstOrCreate(
            ['code' => '42'],
            ['libelle' => 'Personnel', 'prefixe_compte' => '42', 'is_active' => true]
        );

        return static::active()
            ->whereIn('code', ['2', '6', '42'])
            ->orderBy('code')
            ->get();
    }
}
