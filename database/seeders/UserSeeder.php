<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Administrateur par défaut
        User::firstOrCreate(
            ['email' => 'admin@hospital.bj'],
            [
                'name' => 'Administrateur',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ADMIN,
                'is_active' => true,
                'poste' => 'Administrateur Système',
                'email_verified_at' => now(),
            ]
        );

        // Comptable
        User::firstOrCreate(
            ['email' => 'comptable@hospital.bj'],
            [
                'name' => 'Jean Comptable',
                'password' => Hash::make('password'),
                'role' => User::ROLE_COMPTABLE,
                'is_active' => true,
                'poste' => 'Chef Comptable',
                'telephone' => '+229 97 00 00 01',
                'email_verified_at' => now(),
            ]
        );

        // Gestionnaire
        User::firstOrCreate(
            ['email' => 'gestionnaire@hospital.bj'],
            [
                'name' => 'Marie Gestionnaire',
                'password' => Hash::make('password'),
                'role' => User::ROLE_GESTIONNAIRE,
                'is_active' => true,
                'poste' => 'Gestionnaire des Achats',
                'telephone' => '+229 97 00 00 02',
                'email_verified_at' => now(),
            ]
        );

        // Utilisateur standard
        User::firstOrCreate(
            ['email' => 'user@hospital.bj'],
            [
                'name' => 'Pierre Utilisateur',
                'password' => Hash::make('password'),
                'role' => User::ROLE_USER,
                'is_active' => true,
                'poste' => 'Agent Comptable',
                'telephone' => '+229 97 00 00 03',
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('Utilisateurs créés avec succès !');
        $this->command->info('Connexion: admin@hospital.bj / password');
    }
}
