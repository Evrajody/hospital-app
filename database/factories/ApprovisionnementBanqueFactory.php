<?php

namespace Database\Factories;

use App\Models\ApprovisionnementBanque;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApprovisionnementBanqueFactory extends Factory
{
    protected $model = ApprovisionnementBanque::class;

    public function definition(): array
    {
        return [
            'compte_bancaire_id' => CompteBancaireFactory::new(),
            'reference_bordereau' => fake()->bothify('BORD-####'),
            'date_depot' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'montant' => 200000,
        ];
    }
}
