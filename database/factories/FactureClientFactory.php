<?php

namespace Database\Factories;

use App\Models\FactureClient;
use Illuminate\Database\Eloquent\Factories\Factory;

class FactureClientFactory extends Factory
{
    protected $model = FactureClient::class;

    public function definition(): array
    {
        return [
            'reference' => fake()->unique()->numerify('####/01/26'),
            'date_facture' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'montant' => 100000,
            'ristourne' => 0,
            'client_id' => ClientFactory::new(),
            'montant_paye' => 0,
            'statut' => FactureClient::STATUT_NON_PAYEE,
        ];
    }
}
