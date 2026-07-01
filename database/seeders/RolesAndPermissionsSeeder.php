<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Permissions par module
        $permissions = [
            // Fournisseurs
            'fournisseurs.voir',
            'fournisseurs.creer',
            'fournisseurs.modifier',
            'fournisseurs.supprimer',

            // Factures fournisseurs
            'factures-fournisseurs.voir',
            'factures-fournisseurs.creer',
            'factures-fournisseurs.modifier',
            'factures-fournisseurs.supprimer',
            'factures-fournisseurs.desolder',
            'factures-fournisseurs.soldes.voir',
            'factures-fournisseurs.bordereau.voir',

            // Règlements fournisseurs
            'reglements-fournisseurs.voir',
            'reglements-fournisseurs.creer',
            'reglements-fournisseurs.modifier',
            'reglements-fournisseurs.supprimer',

            // Clients
            'clients.voir',
            'clients.creer',
            'clients.modifier',
            'clients.supprimer',

            // Factures clients
            'factures-clients.voir',
            'factures-clients.creer',
            'factures-clients.modifier',
            'factures-clients.supprimer',
            'factures-clients.desolder',

            // Règlements clients
            'reglements-clients.voir',
            'reglements-clients.creer',
            'reglements-clients.modifier',
            'reglements-clients.supprimer',

            // Plan comptable
            'plan-comptable.voir',
            'plan-comptable.modifier',
            'plan-comptable.supprimer',

            // Banques
            'banques.voir',
            'banques.creer',
            'banques.modifier',
            'banques.supprimer',

            // Rapports (permissions découpées par type de rapport)
            'rapports-clients.voir',
            'rapports-fournisseurs.voir',
            'rapports-banques.voir',

            // Administration
            'utilisateurs.voir',
            'utilisateurs.creer',
            'utilisateurs.modifier',
            'utilisateurs.supprimer',
            'roles.voir',
            'roles.creer',
            'roles.modifier',
            'roles.supprimer',
            'parametres.voir',
            'parametres.modifier',

            // Journal d'activité
            'journal.voir',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Nettoyage : l'ancienne permission globale « rapports.voir » est remplacée
        // par les trois permissions découpées (clients / fournisseurs / banques).
        Permission::where('name', 'rapports.voir')->delete();

        // Nettoyage : l'étape de validation des factures fournisseurs a été supprimée
        // (plus de brouillon/validation), donc la permission associée n'existe plus.
        Permission::where('name', 'factures-fournisseurs.valider')->delete();

        // ----- Rôles -----

        // ADMINISTRATEUR : par défaut, uniquement l'administration (utilisateurs,
        // rôles & permissions, paramètres, journal). Le métier (fournisseurs, clients,
        // factures, règlements, banques, plan comptable, rapports) n'est PAS accordé
        // par défaut — l'admin peut se l'accorder lui-même via la matrice des rôles.
        $admin = Role::firstOrCreate(['name' => User::ROLE_ADMIN_NAME, 'guard_name' => 'web']);
        $admin->syncPermissions([
            'utilisateurs.voir', 'utilisateurs.creer', 'utilisateurs.modifier', 'utilisateurs.supprimer',
            'roles.voir', 'roles.creer', 'roles.modifier', 'roles.supprimer',
            'parametres.voir', 'parametres.modifier',
            'journal.voir',
        ]);

        // CHEF SERVICE COMPTABILITÉ : supervise toute la comptabilité (clients +
        // fournisseurs + banques), peut valider. Aucun accès au module Administration.
        $chefCompta = Role::firstOrCreate(['name' => User::ROLE_CHEF_COMPTA_NAME, 'guard_name' => 'web']);
        $chefCompta->syncPermissions([
            'fournisseurs.voir', 'fournisseurs.creer', 'fournisseurs.modifier', 'fournisseurs.supprimer',
            'factures-fournisseurs.voir', 'factures-fournisseurs.creer', 'factures-fournisseurs.modifier', 'factures-fournisseurs.supprimer', 'factures-fournisseurs.desolder',
            'factures-fournisseurs.soldes.voir', 'factures-fournisseurs.bordereau.voir',
            'reglements-fournisseurs.voir', 'reglements-fournisseurs.creer', 'reglements-fournisseurs.modifier', 'reglements-fournisseurs.supprimer',
            'clients.voir', 'clients.creer', 'clients.modifier', 'clients.supprimer',
            'factures-clients.voir', 'factures-clients.creer', 'factures-clients.modifier', 'factures-clients.supprimer', 'factures-clients.desolder',
            'reglements-clients.voir', 'reglements-clients.creer', 'reglements-clients.modifier', 'reglements-clients.supprimer',
            'plan-comptable.voir', 'plan-comptable.modifier', 'plan-comptable.supprimer',
            'banques.voir', 'banques.creer', 'banques.modifier', 'banques.supprimer',
            'rapports-clients.voir', 'rapports-fournisseurs.voir', 'rapports-banques.voir',
        ]);

        // GESTIONNAIRE FOURNISSEURS : opère sur le module Fournisseurs uniquement.
        $gestFournisseurs = Role::firstOrCreate(['name' => User::ROLE_GEST_FOURNISSEURS_NAME, 'guard_name' => 'web']);
        $gestFournisseurs->syncPermissions([
            'fournisseurs.voir', 'fournisseurs.creer', 'fournisseurs.modifier',
            'factures-fournisseurs.voir', 'factures-fournisseurs.creer', 'factures-fournisseurs.modifier', 'factures-fournisseurs.desolder',
            'factures-fournisseurs.soldes.voir', 'factures-fournisseurs.bordereau.voir',
            'reglements-fournisseurs.voir', 'reglements-fournisseurs.creer', 'reglements-fournisseurs.modifier',
            'plan-comptable.voir',
            'banques.voir',
            'rapports-fournisseurs.voir', 'rapports-banques.voir',
        ]);

        // GESTIONNAIRE CLIENTS : opère sur le module Clients uniquement.
        $gestClients = Role::firstOrCreate(['name' => User::ROLE_GEST_CLIENTS_NAME, 'guard_name' => 'web']);
        $gestClients->syncPermissions([
            'clients.voir', 'clients.creer', 'clients.modifier',
            'factures-clients.voir', 'factures-clients.creer', 'factures-clients.modifier', 'factures-clients.desolder',
            'reglements-clients.voir', 'reglements-clients.creer', 'reglements-clients.modifier',
            'rapports-clients.voir',
        ]);

        // ----- Suppression du rôle Super Administrateur -----
        // On migre d'abord ses comptes vers Administrateur (accès total désormais),
        // puis on supprime le rôle. La colonne legacy `role = 'superadmin'` est
        // également repassée à 'admin'.
        $superRole = Role::where('name', 'SuperAdministrateur')->first();
        if ($superRole) {
            User::role('SuperAdministrateur')->get()->each(function (User $u) {
                $u->update(['role' => User::ROLE_ADMIN]);
                $u->syncRoles([User::ROLE_ADMIN_NAME]);
            });
            $superRole->delete();
        }
        User::where('role', 'superadmin')->update(['role' => User::ROLE_ADMIN]);

        // ----- Nettoyage : supprimer les anciens rôles désormais remplacés -----
        // Les pivots model_has_roles sont retirés automatiquement à la suppression.
        Role::whereIn('name', ['Comptable', 'Gestionnaire', 'Utilisateur'])->get()
            ->each(fn (Role $r) => $r->delete());
    }
}
