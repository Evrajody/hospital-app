<?php

namespace Database\Factories;

use App\Models\FactureFournisseur;
use App\Models\Fournisseur;
use Illuminate\Database\Eloquent\Factories\Factory;

class FactureFournisseurFactory extends Factory
{
    protected $model = FactureFournisseur::class;

    public function definition(): array
    {
        return [
            'numero_piece' => 'PC/'.fake()->unique()->numerify('###/####'),
            'date' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'reference_facture' => fake()->bothify('FAC-####'),
            'fournisseur_id' => FournisseurFactory::new(),
            'libelle' => fake()->sentence(4),
            'montant_facture' => 100000,
            'montant_mo' => 0,
            'avoir' => 0,
            'taux' => 0,
            'assujetti_tva' => false,
            'taux_tva' => 18,
            'statut' => FactureFournisseur::STATUT_VALIDEE,
            'montant_paye' => 0,
        ];
    }

    public function payee(): static
    {
        return $this->state(fn () => ['statut' => FactureFournisseur::STATUT_PAYEE]);
    }
}
