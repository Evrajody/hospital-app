<?php

namespace Database\Factories;

use App\Models\CompteBancaire;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompteBancaireFactory extends Factory
{
    protected $model = CompteBancaire::class;

    public function definition(): array
    {
        return [
            'banque_id' => BanqueFactory::new(),
            'numero_compte' => fake()->numerify('###############'),
            'solde' => 0,
        ];
    }
}
