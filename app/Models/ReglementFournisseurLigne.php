<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReglementFournisseurLigne extends Model
{
    protected $table = 'reglement_fournisseur_lignes';

    protected $fillable = [
        'reglement_id',
        'compte_id',
        'montant',
        'libelle',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
    ];

    public function reglement(): BelongsTo
    {
        return $this->belongsTo(ReglementFournisseur::class, 'reglement_id');
    }

    public function compte(): BelongsTo
    {
        return $this->belongsTo(CompteComptable::class, 'compte_id');
    }
}
