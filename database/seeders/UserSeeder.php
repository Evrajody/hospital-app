<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Mot de passe par défaut commun (respecte la politique : 8+, maj/min/chiffre/symbole).
     * À faire changer par l'utilisateur à la première connexion.
     */
    private const DEFAULT_PASSWORD = 'Menontin@2026';

    /**
     * Domaine email par défaut (les comptes sont dérivés du pseudo).
     */
    private const EMAIL_DOMAIN = 'hospital.bj';

    public function run(): void
    {
        // ⚠️ Les rôles Spatie doivent exister AVANT : DatabaseSeeder lance
        // RolesAndPermissionsSeeder en premier.

        // ----- Super administrateurs (hors fichier Excel) -----
        $superAdmins = [
            ['name' => 'GBEDEDJI Ulrich',   'email' => 'ugbededji@gmail.com'],
            ['name' => 'GANDIGBE Gildas',   'email' => 'agandigbe@gmail.com'],
            ['name' => 'HOUNTONDJI Brice',  'email' => 'brice@gmail.com'],
        ];

        foreach ($superAdmins as $sa) {
            $this->upsertUser(
                email: $sa['email'],
                name: $sa['name'],
                legacyRole: User::ROLE_SUPER_ADMIN,
                roles: [User::ROLE_SUPER_ADMIN_NAME],
                poste: 'Super Administrateur',
            );
        }

        // ----- Utilisateurs issus du fichier « User SYSGEF.xlsx » -----
        // Dédoublonnés par pseudo. Une personne présente dans les sections
        // FOURNISSEURS et CLIENTS reçoit les DEUX rôles Gestionnaire.
        $F = User::ROLE_GEST_FOURNISSEURS_NAME;
        $C = User::ROLE_GEST_CLIENTS_NAME;

        // [pseudo, nom complet, poste, rôles Spatie]
        $users = [
            ['ADMIN',     'Administrateur',           'Administrateur',          [User::ROLE_ADMIN_NAME]],
            ['OSEMEVO',   'SEMEVO Odette',            'Gestionnaire',            [$F, $C]],
            ['CAISSEGBE', 'AÏSSEGBE Contant Jonas',   'Gestionnaire',            [$F, $C]],
            ['NALLADAYE', 'Nazaire ALLADAYE',         'Gestionnaire',            [$F, $C]],
            ['SGADO',     'GADO Samirath',            'Gestionnaire',            [$F]],
            ['REUEL',     'JOHNSON SILVERE',          'Gestionnaire',            [$F]],
            ['CASSEDE',   'HOUNSOU ASSEDE CLEMENCE',  'Gestionnaire',            [$F, $C]],
            ['DTINHAN',   'TINHAN Diane',             'Gestionnaire',            [$F]],
            ['FAKAKPO',   'AKAKPO Franck',            'Gestionnaire',            [$F]],
            ['ATRIPHENE', 'ALLADAYE TRIPHENE',        'Gestionnaire',            [$F]],
            ['BTONA',     'TONA Bérenger',            'Gestionnaire',            [$F]],
            ['OGLELE',    'GLELE Ornelli',            'Gestionnaire',            [$F, $C]],
            ['SJOHNSON',  'JOHNSON Silvère',          'Gestionnaire',            [$C]],
            ['OAGUIAR',   'AGUIAR Odile',             'Gestionnaire',            [$C]],
        ];

        foreach ($users as [$pseudo, $name, $poste, $roles]) {
            $email = strtolower($pseudo) . '@' . self::EMAIL_DOMAIN;

            $legacyRole = match (true) {
                in_array(User::ROLE_ADMIN_NAME, $roles, true) => User::ROLE_ADMIN,
                default => User::ROLE_GESTIONNAIRE,
            };

            $this->upsertUser(
                email: $email,
                name: $name,
                legacyRole: $legacyRole,
                roles: $roles,
                poste: $poste,
            );
        }

        $this->command->info('Utilisateurs SYSGEF créés : ' . (count($superAdmins) + count($users)) . ' comptes.');
        $this->command->info('Mot de passe par défaut : ' . self::DEFAULT_PASSWORD);
    }

    /**
     * Crée l'utilisateur s'il n'existe pas (sans écraser un mot de passe déjà changé),
     * puis (re)synchronise systématiquement ses rôles — le seeder reste idempotent.
     */
    private function upsertUser(string $email, string $name, string $legacyRole, array $roles, string $poste): void
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                // Le cast 'hashed' du modèle hache automatiquement.
                'password' => self::DEFAULT_PASSWORD,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Toujours aligner nom / poste / rôle legacy + rôles Spatie (idempotent).
        $user->update([
            'name' => $name,
            'role' => $legacyRole,
            'poste' => $poste,
        ]);
        $user->syncRoles($roles);
    }
}
