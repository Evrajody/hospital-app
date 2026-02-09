<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovisionnementBanque extends Model
{
    use SoftDeletes;

    protected $table = 'approvisionnements_banques';

    protected $fillable = [
        'compte_bancaire_id',
        'date_depot',
        'montant',
        'observations',
        'piece_jointe',
        'created_by',
    ];

    protected $casts = [
        'date_depot' => 'date',
        'montant' => 'decimal:2',
    ];

    /**
     * Relation avec le compte bancaire
     */
    public function compteBancaire(): BelongsTo
    {
        return $this->belongsTo(CompteBancaire::class);
    }

    /**
     * Relation avec l'utilisateur créateur
     */
    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
