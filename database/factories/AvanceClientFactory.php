<?php

namespace Database\Factories;

use App\Models\AvanceClient;
use Illuminate\Database\Eloquent\Factories\Factory;

class AvanceClientFactory extends Factory
{
    protected $model = AvanceClient::class;

    public function definition(): array
    {
        return [
            'client_id' => ClientFactory::new(),
            'societe_emettrice' => fake()->company(),
            'numero_cheque' => fake()->numerify('#######'),
            'date_cheque' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'montant' => 300000,
            'montant_utilise' => 0,
            'statut' => AvanceClient::STATUT_DISPONIBLE,
        ];
    }
}
