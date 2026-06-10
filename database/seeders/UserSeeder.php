<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Comptes utilisateurs de référence (issus de l'environnement réel).
     *
     * - Idempotent : clé = email. Le mot de passe n'est posé qu'à la CRÉATION
     *   (firstOrCreate) ; un mot de passe changé ensuite dans l'app n'est PAS écrasé.
     * - Les mots de passe sont les hash bcrypt réels (le cast 'hashed' du modèle
     *   détecte une valeur déjà hachée et ne la re-hache pas) → les utilisateurs
     *   conservent leurs mots de passe.
     * - Les rôles Spatie sont resynchronisés à chaque passage.
     *
     * ⚠️ Les hash sont en quotes SIMPLES (sinon PHP interpolerait les `$`).
     * NB : seuls les comptes actifs (is_active=t, non supprimés) sont seedés.
     * Les comptes « @legacy.local » sont des artefacts de l'import legacy
     * (recréés par `legacy:migrate`) et ne sont donc pas inclus ici.
     */
    public function run(): void
    {
        // [nom, email, hash bcrypt, rôle (colonne legacy), rôles Spatie (tableau), poste]
        $F = User::ROLE_GEST_FOURNISSEURS_NAME;
        $C = User::ROLE_GEST_CLIENTS_NAME;

        $users = [
            ['GANDIGBE Gildas',           'agandigbe@gmail.com',         '$2y$12$Ls2hho9b45RKu.2BsM45A.g3seSUdMeEuwOBhTm63J0gIxXxMrVpy', User::ROLE_ADMIN,       [User::ROLE_ADMIN_NAME],     'Administrateur'],
            ['Administrateur',            'admin@hospital.bj',           '$2y$12$paS808w/xb7EkPszcwY/GuS5kow4vwvzdlnG.612onYVrRxqBIWn6', User::ROLE_ADMIN,       [User::ROLE_ADMIN_NAME],     'Administrateur'],
            ['GADO Totomsokiwe Samirath', 'totomsokiwe@gmail.com',       '$2y$12$IfQo7qb8bzDzjSWCWrU2DutWXa0P1frfUzKfNO3V.khi45yyAG5DK', User::ROLE_GESTIONNAIRE, [$F],                       'Assistante comptable'],
            ['HOUNSOU ASSEDE Clémence',   'hopitaldemenontin@gmail.com', '$2y$12$jnI8kGxYjWC6Nm1BlfD3fux1ZOYfS/VKmeqcH4f7DP0xKVu3VP1te', User::ROLE_GESTIONNAIRE, [$F, $C],                   'Comptable'],
            ['ALLADAYE Triphène',         'natdetri@gmail.com',          '$2y$12$6oZFJ3M8BTXVf6Wn867tMuib5fgzF43LinKCzMcq3dvPmy0nOtzZe', User::ROLE_GESTIONNAIRE, [$F, $C],                   'Assistante comptable'],
            ['TONA Bérenger',             'berengertona22@gmail.com',    '$2y$12$monrlsPE9DpvDzWLbdP43OwEnS4M2BbKTTPdWPXKmdOiOguzoiW4y', User::ROLE_GESTIONNAIRE, [$F, $C],                   'Assistant Comptable'],
            ['OUSSOU Gilles',             'oussougil@yahoo.fr',          '$2y$12$NNUVIuGu8NV21NKzlGGqru27qH6E8RIAsQXjPyjOz3C4X0og0PD62', User::ROLE_ADMIN,       [User::ROLE_ADMIN_NAME],     'RSI'],
        ];

        foreach ($users as [$name, $email, $hash, $legacyRole, $spatieRoles, $poste]) {
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => $hash,            // hash bcrypt déjà calculé (non re-haché)
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            // Aligne nom / rôle legacy / poste (idempotent) sans toucher au mot de passe.
            $user->update([
                'name' => $name,
                'role' => $legacyRole,
                'poste' => $poste,
                'is_active' => true,
            ]);

            $user->syncRoles($spatieRoles);
        }

        $this->command->info('Utilisateurs de référence : ' . count($users) . ' comptes (mots de passe réels conservés).');
    }
}
