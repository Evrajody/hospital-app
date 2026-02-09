<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompteBancaire extends Model
{
    use SoftDeletes;

    protected $table = 'comptes_bancaires';

    protected $fillable = [
        'banque_id',
        'numero_compte',
        'compte_ohada_id',
        'solde',
        'observations',
    ];

    protected $casts = [
        'solde' => 'decimal:2',
    ];

    /**
     * Relation avec la banque
     */
    public function banque(): BelongsTo
    {
        return $this->belongsTo(Banque::class);
    }

    /**
     * Relation avec le compte OHADA
     */
    public function compteOhada(): BelongsTo
    {
        return $this->belongsTo(CompteComptable::class, 'compte_ohada_id');
    }

    /**
     * Relation avec les approvisionnements
     */
    public function approvisionnements(): HasMany
    {
        return $this->hasMany(ApprovisionnementBanque::class, 'compte_bancaire_id');
    }

    /**
     * Ajouter un approvisionnement et mettre à jour le solde
     */
    public function approvisionner(float $montant, string $dateDepot, ?string $observations = null, ?string $pieceJointe = null): ApprovisionnementBanque
    {
        $approvisionnement = $this->approvisionnements()->create([
            'date_depot' => $dateDepot,
            'montant' => $montant,
            'observations' => $observations,
            'piece_jointe' => $pieceJointe,
            'created_by' => auth()->id(),
        ]);

        $this->increment('solde', $montant);

        return $approvisionnement;
    }
}
