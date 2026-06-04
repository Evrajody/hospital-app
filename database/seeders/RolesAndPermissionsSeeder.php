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

        // ----- Rôles -----

        // SUPER ADMINISTRATEUR : accès total (toutes les permissions).
        $superAdmin = Role::firstOrCreate(['name' => User::ROLE_SUPER_ADMIN_NAME, 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::all());

        // ADMINISTRATEUR : uniquement les éléments du module « Paramètres »
        // (utilisateurs, rôles & permissions, paramètres/établissement, taux fiscaux,
        // journal d'activité). N'a AUCUN accès aux modules métier ni aux rapports,
        // et ne peut PAS créer/attribuer le rôle Super Administrateur (cf. contrôleurs).
        $admin = Role::firstOrCreate(['name' => User::ROLE_ADMIN_NAME, 'guard_name' => 'web']);
        $admin->syncPermissions([
            'utilisateurs.voir', 'utilisateurs.creer', 'utilisateurs.modifier', 'utilisateurs.supprimer',
            'roles.voir', 'roles.creer', 'roles.modifier', 'roles.supprimer',
            'parametres.voir', 'parametres.modifier',
            'journal.voir',
        ]);

        $comptable = Role::firstOrCreate(['name' => 'Comptable', 'guard_name' => 'web']);
        $comptable->syncPermissions([
            'fournisseurs.voir', 'fournisseurs.creer', 'fournisseurs.modifier',
            'factures-fournisseurs.voir', 'factures-fournisseurs.creer', 'factures-fournisseurs.modifier', 'factures-fournisseurs.valider',
            'reglements-fournisseurs.voir', 'reglements-fournisseurs.creer', 'reglements-fournisseurs.modifier',
            'clients.voir', 'clients.creer', 'clients.modifier',
            'factures-clients.voir', 'factures-clients.creer', 'factures-clients.modifier',
            'reglements-clients.voir', 'reglements-clients.creer', 'reglements-clients.modifier',
            'plan-comptable.voir',
            'banques.voir',
            // Rapports : clients, fournisseurs et banques
            'rapports-clients.voir', 'rapports-fournisseurs.voir', 'rapports-banques.voir',
        ]);

        $gestionnaire = Role::firstOrCreate(['name' => 'Gestionnaire', 'guard_name' => 'web']);
        $gestionnaire->syncPermissions([
            'fournisseurs.voir',
            'factures-fournisseurs.voir',
            'reglements-fournisseurs.voir',
            'clients.voir',
            'factures-clients.voir',
            'reglements-clients.voir',
            // Rapports : clients, fournisseurs et banques
            'rapports-clients.voir', 'rapports-fournisseurs.voir', 'rapports-banques.voir',
        ]);

        $utilisateur = Role::firstOrCreate(['name' => 'Utilisateur', 'guard_name' => 'web']);
        $utilisateur->syncPermissions([
            'fournisseurs.voir',
            'factures-fournisseurs.voir',
            'reglements-fournisseurs.voir',
            'clients.voir',
            'factures-clients.voir',
            'reglements-clients.voir',
        ]);

        // Compte super administrateur par défaut (le compte admin historique devient super admin)
        $superAdminUser = User::firstOrCreate(
            ['email' => 'admin@hospital.bj'],
            [
                'name' => 'Super Administrateur',
                'password' => 'password',
                'is_active' => true,
                'poste' => 'Super Administrateur',
            ]
        );
        $superAdminUser->update(['role' => User::ROLE_SUPER_ADMIN, 'name' => 'Super Administrateur']);
        $superAdminUser->syncRoles([User::ROLE_SUPER_ADMIN_NAME]);

        // Compte administrateur (paramètres uniquement) — exemple
        $adminUser = User::firstOrCreate(
            ['email' => 'administrateur@hospital.bj'],
            [
                'name' => 'Administrateur',
                'password' => 'password',
                'is_active' => true,
                'poste' => 'Administrateur (Paramètres)',
            ]
        );
        $adminUser->update(['role' => User::ROLE_ADMIN]);
        $adminUser->syncRoles([User::ROLE_ADMIN_NAME]);
    }
}
