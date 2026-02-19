<?php


namespace Database\Seeders;


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
        // Créer les utilisateurs en premier (référencés par d'autres tables)
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
