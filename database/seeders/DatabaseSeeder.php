<?php


namespace Database\Seeders;


use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Seeder;


class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // Rôles & permissions d'abord (les utilisateurs s'y rattachent)
        $this->call(RolesAndPermissionsSeeder::class);

        // Utilisateurs (super admins + comptes issus du fichier SYSGEF)
        $this->call(UserSeeder::class);

        // Plan comptable OHADA
        $this->call(PlanComptableOhadaSeeder::class);

        // Taux fiscaux (TVA, AIB)
        $this->call(TauxFiscalSeeder::class);

        // Fournisseurs et factures de test
        // $this->call(FournisseurFactureSeeder::class);

        // Règlements fournisseurs de test
        // $this->call(ReglementFournisseurSeeder::class);
    }
}
