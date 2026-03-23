<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $table = 'activity_logs';

    protected $fillable = [
        'action',
        'module',
        'description',
        'subject_type',
        'subject_id',
        'properties',
        'user_id',
        'user_name',
        'ip_address',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    // ==========================================
    // RELATIONS
    // ==========================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ==========================================
    // MÉTHODES STATIQUES
    // ==========================================

    /**
     * Enregistrer une activité
     */
    public static function log(
        string $action,
        string $module,
        string $description,
        ?Model $subject = null,
        ?array $properties = null
    ): self {
        $user = auth()->user();

        return self::create([
            'action' => $action,
            'module' => $module,
            'description' => $description,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->id,
            'properties' => $properties,
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'ip_address' => request()?->ip(),
        ]);
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    public function getActionLibelleAttribute(): string
    {
        return match($this->action) {
            'create' => 'Création',
            'update' => 'Modification',
            'delete' => 'Suppression',
            'validate' => 'Validation',
            'cancel' => 'Annulation',
            'pay' => 'Paiement',
            'settle' => 'Solde',
            'login' => 'Connexion',
            'logout' => 'Déconnexion',
            default => ucfirst($this->action),
        };
    }

    public function getModuleLibelleAttribute(): string
    {
        return match($this->module) {
            'facture_fournisseur' => 'Facture Fournisseur',
            'reglement_fournisseur' => 'Règlement Fournisseur',
            'facture_client' => 'Facture Client',
            'reglement_client' => 'Règlement Client',
            'fournisseur' => 'Fournisseur',
            'client' => 'Client',
            'banque' => 'Banque',
            'compte_bancaire' => 'Compte Bancaire',
            'plan_comptable' => 'Plan Comptable',
            'utilisateur' => 'Utilisateur',
            'role' => 'Rôle',
            'parametres' => 'Paramètres',
            'auth' => 'Authentification',
            default => ucfirst(str_replace('_', ' ', $this->module)),
        };
    }

    public function getActionCouleurAttribute(): string
    {
        return match($this->action) {
            'create' => 'success',
            'update' => 'warning',
            'delete' => 'danger',
            'validate' => 'success',
            'cancel' => 'danger',
            'pay' => 'primary',
            'settle' => 'success',
            'login' => 'info',
            'logout' => 'info',
            default => 'info',
        };
    }

    /**
     * Convertir en tableau pour l'API
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'action_libelle' => $this->action_libelle,
            'action_couleur' => $this->action_couleur,
            'module' => $this->module,
            'module_libelle' => $this->module_libelle,
            'description' => $this->description,
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'properties' => $this->properties,
            'user_id' => $this->user_id,
            'user_name' => $this->user_name,
            'ip_address' => $this->ip_address,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'created_at_human' => $this->created_at?->diffForHumans(),
        ];
    }
}
