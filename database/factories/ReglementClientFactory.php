<?php

namespace Database\Factories;

use App\Models\FactureClient;
use App\Models\ReglementClient;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReglementClientFactory extends Factory
{
    protected $model = ReglementClient::class;

    public function definition(): array
    {
        return [
            'date_reglement' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'facture_id' => FactureClientFactory::new(),
            'client_id' => ClientFactory::new(),
            'montant' => 50000,
            'type_reglement' => ReglementClient::TYPE_REGLEMENT,
            'institution' => fake()->randomElement(['BOA', 'ECOBANK', 'UBA', 'NSIA']),
            'reference_cheque' => fake()->numerify('#######'),
        ];
    }

    public function pourFacture(FactureClient $facture): static
    {
        return $this->state(fn () => [
            'facture_id' => $facture->id,
            'client_id' => $facture->client_id,
        ]);
    }

    public function perte(): static
    {
        return $this->state(fn () => ['type_reglement' => ReglementClient::TYPE_PERTE]);
    }
}
