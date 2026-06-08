<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasRoles;

    /**
     * Constantes pour les rôles (valeurs de la colonne `role`)
     */
    const ROLE_SUPER_ADMIN = 'superadmin';
    const ROLE_ADMIN = 'admin';
    const ROLE_COMPTABLE = 'comptable';
    const ROLE_GESTIONNAIRE = 'gestionnaire';
    const ROLE_USER = 'user';

    /**
     * Noms des rôles Spatie (autorisation réelle)
     */
    const ROLE_SUPER_ADMIN_NAME = 'SuperAdministrateur';
    const ROLE_ADMIN_NAME = 'Administrateur';
    const ROLE_CHEF_COMPTA_NAME = 'Chef service comptabilité';
    const ROLE_GEST_FOURNISSEURS_NAME = 'Gestionnaire Fournisseurs';
    const ROLE_GEST_CLIENTS_NAME = 'Gestionnaire Clients';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'telephone',
        'poste',
        'signature_path',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Vérifier si l'utilisateur est super administrateur (accès total).
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole(self::ROLE_SUPER_ADMIN_NAME) || $this->role === self::ROLE_SUPER_ADMIN;
    }

    /**
     * Vérifier si l'utilisateur est admin
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN || $this->isSuperAdmin();
    }

    /**
     * Vérifier si l'utilisateur est comptable
     */
    public function isComptable(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_COMPTABLE]);
    }

    /**
     * Vérifier si l'utilisateur est gestionnaire
     */
    public function isGestionnaire(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_GESTIONNAIRE]);
    }

    /**
     * Obtenir le libellé du rôle
     */
    public function getRoleLibelleAttribute(): string
    {
        return match($this->role) {
            self::ROLE_SUPER_ADMIN => 'Super Administrateur',
            self::ROLE_ADMIN => 'Administrateur',
            self::ROLE_COMPTABLE => 'Comptable',
            self::ROLE_GESTIONNAIRE => 'Gestionnaire',
            self::ROLE_USER => 'Utilisateur',
            default => 'Utilisateur',
        };
    }

    /**
     * Obtenir les rôles disponibles
     */
    public static function getRoles(): array
    {
        return [
            ['value' => self::ROLE_ADMIN, 'label' => 'Administrateur'],
            ['value' => self::ROLE_COMPTABLE, 'label' => 'Comptable'],
            ['value' => self::ROLE_GESTIONNAIRE, 'label' => 'Gestionnaire'],
            ['value' => self::ROLE_USER, 'label' => 'Utilisateur'],
        ];
    }

    /**
     * Scope pour les utilisateurs actifs
     */
    public function scopeActif($query)
    {
        return $query->where('is_active', true);
    }

    // Le scope role() est maintenant fourni par le trait HasRoles de Spatie
}
