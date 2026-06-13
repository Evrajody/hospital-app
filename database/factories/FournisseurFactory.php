<?php

namespace Database\Factories;

use App\Models\Fournisseur;
use Illuminate\Database\Eloquent\Factories\Factory;

class FournisseurFactory extends Factory
{
    protected $model = Fournisseur::class;

    public function definition(): array
    {
        return [
            'nom' => fake()->company(),
            'type_fournisseur' => Fournisseur::TYPE_MEDICAMENTS,
            'telephone' => fake()->numerify('##########'),
            'email' => fake()->unique()->companyEmail(),
            'pays' => 'BJ',
            'ifu' => fake()->numerify('#############'),
        ];
    }
}
