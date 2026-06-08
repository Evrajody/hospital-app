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
            'factures-fournisseurs.valider',

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

            // Règlements clients
            'reglements-clients.voir',
            'reglements-clients.creer',
            'reglements-clients.modifier',
            'reglements-clients.supprimer',

            // Plan comptable
            'plan-comptable.voir',
            'plan-comptable.modifier',

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

        // ----- Rôles (rigoureusement les 5 rôles métier) -----

        // SUPER ADMINISTRATEUR : accès total (toutes les permissions).
        $superAdmin = Role::firstOrCreate(['name' => User::ROLE_SUPER_ADMIN_NAME, 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::all());

        // ADMINISTRATEUR : uniquement les éléments du module « Paramètres »
        // (utilisateurs, rôles & permissions, paramètres/établissement, taux fiscaux,
        // journal d'activité). N'a AUCUN accès aux modules métier ni aux rapports,
        // et ne doit RIEN savoir du rôle Super Administrateur (cf. contrôleurs : le
        // rôle et les comptes super admin lui sont masqués).
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
            'factures-fournisseurs.voir', 'factures-fournisseurs.creer', 'factures-fournisseurs.modifier', 'factures-fournisseurs.supprimer', 'factures-fournisseurs.valider',
            'reglements-fournisseurs.voir', 'reglements-fournisseurs.creer', 'reglements-fournisseurs.modifier', 'reglements-fournisseurs.supprimer',
            'clients.voir', 'clients.creer', 'clients.modifier', 'clients.supprimer',
            'factures-clients.voir', 'factures-clients.creer', 'factures-clients.modifier', 'factures-clients.supprimer',
            'reglements-clients.voir', 'reglements-clients.creer', 'reglements-clients.modifier', 'reglements-clients.supprimer',
            'plan-comptable.voir', 'plan-comptable.modifier',
            'banques.voir', 'banques.creer', 'banques.modifier', 'banques.supprimer',
            'rapports-clients.voir', 'rapports-fournisseurs.voir', 'rapports-banques.voir',
        ]);

        // GESTIONNAIRE FOURNISSEURS : opère sur le module Fournisseurs uniquement.
        $gestFournisseurs = Role::firstOrCreate(['name' => User::ROLE_GEST_FOURNISSEURS_NAME, 'guard_name' => 'web']);
        $gestFournisseurs->syncPermissions([
            'fournisseurs.voir', 'fournisseurs.creer', 'fournisseurs.modifier',
            'factures-fournisseurs.voir', 'factures-fournisseurs.creer', 'factures-fournisseurs.modifier',
            'reglements-fournisseurs.voir', 'reglements-fournisseurs.creer', 'reglements-fournisseurs.modifier',
            'plan-comptable.voir',
            'banques.voir',
            'rapports-fournisseurs.voir', 'rapports-banques.voir',
        ]);

        // GESTIONNAIRE CLIENTS : opère sur le module Clients uniquement.
        $gestClients = Role::firstOrCreate(['name' => User::ROLE_GEST_CLIENTS_NAME, 'guard_name' => 'web']);
        $gestClients->syncPermissions([
            'clients.voir', 'clients.creer', 'clients.modifier',
            'factures-clients.voir', 'factures-clients.creer', 'factures-clients.modifier',
            'reglements-clients.voir', 'reglements-clients.creer', 'reglements-clients.modifier',
            'rapports-clients.voir',
        ]);

        // ----- Nettoyage : supprimer les anciens rôles désormais remplacés -----
        // (Comptable → Chef service comptabilité, Gestionnaire → Gestionnaire
        //  Fournisseurs/Clients, Utilisateur supprimé). Les pivots model_has_roles
        //  sont retirés automatiquement à la suppression du rôle.
        Role::whereIn('name', ['Comptable', 'Gestionnaire', 'Utilisateur'])->get()
            ->each(fn (Role $r) => $r->delete());
    }
}
