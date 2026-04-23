<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use SoftDeletes;

    protected $table = 'clients';

    protected $fillable = [
        'nom',
        'telephone',
        'ifu',
        'type_client',
        'observation',
        'adresse',
        'compte_comptable_id',
    ];

    public const TYPES = ['societe', 'divers', 'personnel', 'autre'];

    public const TYPES_LABELS = [
        'societe' => 'Société',
        'divers' => 'Divers',
        'personnel' => 'Personnel',
        'autre' => 'Autre',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ==========================================
    // RELATIONS
    // ==========================================

    public function compteComptable(): BelongsTo
    {
        return $this->belongsTo(CompteComptable::class, 'compte_comptable_id');
    }

    public function facturesClient(): HasMany
    {
        return $this->hasMany(FactureClient::class);
    }

    public function reglementsClient(): HasMany
    {
        return $this->hasMany(ReglementClient::class);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeRecherche(Builder $query, string $terme): Builder
    {
        return $query->where(function ($q) use ($terme) {
            $q->where('nom', 'ILIKE', "%{$terme}%")
              ->orWhere('telephone', 'LIKE', "%{$terme}%")
              ->orWhere('ifu', 'LIKE', "%{$terme}%");
        });
    }

    // ==========================================
    // MÉTHODES D'INSTANCE
    // ==========================================

    public function aCompteComptable(): bool
    {
        return $this->compte_comptable_id !== null;
    }

    public function getNumeroCompte(): ?string
    {
        return $this->compteComptable?->numero_compte;
    }

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'telephone' => $this->telephone,
            'ifu' => $this->ifu,
            'type_client' => $this->type_client,
            'type_client_label' => self::TYPES_LABELS[$this->type_client] ?? $this->type_client,
            'observation' => $this->observation,
            'adresse' => $this->adresse,
            'compte_comptable_id' => $this->compte_comptable_id,
            'compte_comptable' => $this->compteComptable ? [
                'id' => $this->compteComptable->id,
                'numero' => $this->compteComptable->numero_compte,
                'libelle' => $this->compteComptable->libelle,
            ] : null,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
