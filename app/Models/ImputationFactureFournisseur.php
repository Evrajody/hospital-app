<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImputationFactureFournisseur extends Model
{
    protected $table = 'imputations_facture_fournisseur';

    protected $fillable = [
        'facture_id',
        'compte_id',
        'montant',
        'libelle',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
    ];

    public function facture(): BelongsTo
    {
        return $this->belongsTo(FactureFournisseur::class, 'facture_id');
    }

    public function compte(): BelongsTo
    {
        return $this->belongsTo(CompteComptable::class, 'compte_id');
    }
}
