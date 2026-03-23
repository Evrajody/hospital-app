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

            // Rapports
            'rapports.voir',

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

        // Rôles
        $admin = Role::firstOrCreate(['name' => 'Administrateur', 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::all());

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
            'rapports.voir',
        ]);

        $gestionnaire = Role::firstOrCreate(['name' => 'Gestionnaire', 'guard_name' => 'web']);
        $gestionnaire->syncPermissions([
            'fournisseurs.voir',
            'factures-fournisseurs.voir',
            'reglements-fournisseurs.voir',
            'clients.voir',
            'factures-clients.voir',
            'reglements-clients.voir',
            'rapports.voir',
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

        // Admin par défaut
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@hospital.bj'],
            [
                'name' => 'Administrateur',
                'password' => 'password',
                'is_active' => true,
                'poste' => 'Administrateur Système',
            ]
        );
        $adminUser->assignRole('Administrateur');
    }
}
