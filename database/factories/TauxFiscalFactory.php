<?php

namespace Database\Factories;

use App\Models\TauxFiscal;
use Illuminate\Database\Eloquent\Factories\Factory;

class TauxFiscalFactory extends Factory
{
    protected $model = TauxFiscal::class;

    public function definition(): array
    {
        return [
            'type' => 'tva',
            'libelle' => 'TVA 18%',
            'taux' => 18,
            'actif' => true,
            'par_defaut' => false,
        ];
    }
}
