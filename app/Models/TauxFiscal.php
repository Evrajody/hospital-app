<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class TauxFiscal extends Model
{
    use SoftDeletes;

    protected $table = 'taux_fiscaux';

    protected $fillable = [
        'type',
        'libelle',
        'taux',
        'actif',
        'par_defaut',
    ];

    protected $casts = [
        'taux' => 'decimal:2',
        'actif' => 'boolean',
        'par_defaut' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeTva(Builder $query): Builder
    {
        return $query->where('type', 'tva');
    }

    public function scopeAib(Builder $query): Builder
    {
        return $query->where('type', 'aib');
    }

    public function scopeActif(Builder $query): Builder
    {
        return $query->where('actif', true);
    }

    public function scopeParDefaut(Builder $query): Builder
    {
        return $query->where('par_defaut', true);
    }

    // ==========================================
    // MÉTHODES STATIQUES
    // ==========================================

    public static function getTvaParDefaut(): float
    {
        $taux = static::tva()->actif()->parDefaut()->first();
        return $taux ? (float) $taux->taux : 18;
    }

    public static function getAibActifs(): array
    {
        return static::aib()->actif()->orderBy('taux')->get()
            ->map(fn ($t) => $t->toApiArray())
            ->toArray();
    }

    public static function getTvaActifs(): array
    {
        return static::tva()->actif()->orderBy('taux')->get()
            ->map(fn ($t) => $t->toApiArray())
            ->toArray();
    }

    // ==========================================
    // MÉTHODES D'INSTANCE
    // ==========================================

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'libelle' => $this->libelle,
            'taux' => (float) $this->taux,
            'actif' => $this->actif,
            'par_defaut' => $this->par_defaut,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
