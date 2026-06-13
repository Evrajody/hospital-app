<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        return [
            'nom' => fake()->company(),
            'telephone' => fake()->numerify('##########'),
            'ifu' => fake()->numerify('#############'),
            'type_client' => 'societe',
            'adresse' => fake()->address(),
        ];
    }
}
