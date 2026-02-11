<?php

namespace Database\Seeders;

use App\Models\Classe;
use Illuminate\Database\Seeder;

class ClasseSeeder extends Seeder
{
    public function run(): void
    {
        $classes = [
            ['code' => '1', 'libelle' => 'Ressources Durables',                 'prefixe_compte' => '1'],
            ['code' => '2', 'libelle' => 'Actif Immobilisé',                    'prefixe_compte' => '2'],
            ['code' => '3', 'libelle' => 'Stocks',                              'prefixe_compte' => '3'],
            ['code' => '4', 'libelle' => 'Tiers',                               'prefixe_compte' => '4'],
            ['code' => '5', 'libelle' => 'Trésorerie',                          'prefixe_compte' => '5'],
            ['code' => '6', 'libelle' => 'Charges des Activités Ordinaires',    'prefixe_compte' => '6'],
            ['code' => '7', 'libelle' => 'Produits des Activités Ordinaires',   'prefixe_compte' => '7'],
            ['code' => '8', 'libelle' => 'Autres Charges et Produits',          'prefixe_compte' => '8'],
        ];

        foreach ($classes as $classe) {
            Classe::updateOrCreate(
                ['code' => $classe['code']],
                $classe
            );
        }

        $this->command->info(count($classes) . ' classes OHADA créées/mises à jour.');
    }
}
