<?php

namespace Database\Factories;

use App\Models\FactureFournisseur;
use App\Models\ReglementFournisseur;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReglementFournisseurFactory extends Factory
{
    protected $model = ReglementFournisseur::class;

    public function definition(): array
    {
        $facture = FactureFournisseurFactory::new();

        return [
            'numero_reglement' => 'REG/'.fake()->unique()->numerify('##/#####'),
            'date_reglement' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'facture_id' => $facture,
            'fournisseur_id' => FournisseurFactory::new(),
            'montant' => 50000,
            'mode_paiement' => ReglementFournisseur::MODE_VIREMENT,
            'statut' => ReglementFournisseur::STATUT_VALIDE,
        ];
    }

    /** Rattache le règlement à une facture existante (et son fournisseur). */
    public function pourFacture(FactureFournisseur $facture): static
    {
        return $this->state(fn () => [
            'facture_id' => $facture->id,
            'fournisseur_id' => $facture->fournisseur_id,
        ]);
    }
}
