<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Banque extends Model
{
    use SoftDeletes;

    protected $table = 'banques';

    protected $fillable = [
        'nom',
    ];

    /**
     * Relation avec les comptes bancaires
     */
    public function comptes(): HasMany
    {
        return $this->hasMany(CompteBancaire::class);
    }

    /**
     * Solde total de tous les comptes
     */
    public function getSoldeTotalAttribute(): float
    {
        return $this->comptes()->sum('solde');
    }
}
