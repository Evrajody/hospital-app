<?php

namespace Database\Factories;

use App\Models\Banque;
use Illuminate\Database\Eloquent\Factories\Factory;

class BanqueFactory extends Factory
{
    protected $model = Banque::class;

    public function definition(): array
    {
        return [
            'nom' => fake()->randomElement(['BOA', 'ECOBANK', 'UBA', 'NSIA', 'ORABANK']).' '.fake()->unique()->numerify('###'),
        ];
    }
}
