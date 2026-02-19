<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TauxFiscal;

class TauxFiscalSeeder extends Seeder
{
    public function run(): void
    {
        $taux = [
            ['type' => 'tva', 'libelle' => 'TVA Standard', 'taux' => 18, 'actif' => true, 'par_defaut' => true],
            ['type' => 'aib', 'libelle' => 'AIB 1%', 'taux' => 1, 'actif' => true, 'par_defaut' => true],
            ['type' => 'aib', 'libelle' => 'AIB 3%', 'taux' => 3, 'actif' => true, 'par_defaut' => false],
            ['type' => 'aib', 'libelle' => 'AIB 5%', 'taux' => 5, 'actif' => true, 'par_defaut' => false],
        ];

        foreach ($taux as $t) {
            TauxFiscal::firstOrCreate(
                ['type' => $t['type'], 'taux' => $t['taux']],
                $t
            );
        }
    }
}
