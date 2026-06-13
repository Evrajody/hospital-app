<?php

namespace Database\Factories;

use App\Models\CompteComptable;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompteComptableFactory extends Factory
{
    protected $model = CompteComptable::class;

    public function definition(): array
    {
        $numero = (string) fake()->unique()->numberBetween(10000, 99999);

        return [
            'numero_compte' => $numero,
            'libelle' => fake()->sentence(3),
            'classe' => (int) substr($numero, 0, 1),
            'niveau' => strlen($numero),
            'type_compte' => CompteComptable::TYPE_ACTIF,
            'is_custom' => false,
        ];
    }

    /** Crée un compte avec un numéro précis (utile pour les hiérarchies). */
    public function numero(string $numeroCompte, ?string $libelle = null): static
    {
        return $this->state(fn () => [
            'numero_compte' => $numeroCompte,
            'classe' => (int) substr($numeroCompte, 0, 1),
            'niveau' => strlen($numeroCompte),
            'libelle' => $libelle ?? 'Compte '.$numeroCompte,
        ]);
    }
}
