<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FournisseurCompte extends Model
{
    protected $table = 'fournisseur_comptes';

    protected $fillable = [
        'fournisseur_id',
        'compte_comptable_id',
        'principal',
    ];

    protected $casts = [
        'principal' => 'boolean',
    ];

    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class);
    }

    public function compteComptable(): BelongsTo
    {
        return $this->belongsTo(CompteComptable::class, 'compte_comptable_id');
    }
}
