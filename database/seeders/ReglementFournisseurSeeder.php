<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ReglementFournisseur;
use App\Models\FactureFournisseur;
use Carbon\Carbon;

class ReglementFournisseurSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer les factures qui peuvent recevoir des paiements
        $factures = FactureFournisseur::whereIn('statut', [
            FactureFournisseur::STATUT_VALIDEE,
            FactureFournisseur::STATUT_PARTIELLEMENT_PAYEE,
        ])->get();

        if ($factures->isEmpty()) {
            $this->command->warn('Aucune facture disponible pour les règlements. Exécutez d\'abord FournisseurFactureSeeder.');
            return;
        }

        // Créer quelques règlements de test
        foreach ($factures as $facture) {
            // 50% de chance de créer un règlement pour cette facture
            if (rand(0, 1) === 0) {
                continue;
            }

            $resteAPayer = $facture->reste_a_payer;
            if ($resteAPayer <= 0) {
                continue;
            }

            // Montant aléatoire entre 30% et 100% du reste à payer
            $pourcentage = rand(30, 100) / 100;
            $montant = round($resteAPayer * $pourcentage, 2);

            // Mode de paiement aléatoire
            $modes = ['virement', 'cheque', 'especes', 'mobile_money'];
            $mode = $modes[array_rand($modes)];

            // Générer une référence selon le mode
            $reference = match($mode) {
                'virement' => 'VIR-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT),
                'cheque' => 'CHQ-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT),
                'mobile_money' => 'MM-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT),
                default => null,
            };

            // Banque pour les modes qui en nécessitent
            $banque = in_array($mode, ['virement', 'cheque'])
                ? ['ORABANK', 'BOA BENIN', 'ECOBANK', 'UBA'][array_rand(['ORABANK', 'BOA BENIN', 'ECOBANK', 'UBA'])]
                : null;

            // Date de règlement (entre la date de facture et aujourd'hui)
            $dateFacture = $facture->date ?? Carbon::now()->subDays(30);
            $dateReglement = Carbon::parse($dateFacture)->addDays(rand(5, 20));
            if ($dateReglement > Carbon::now()) {
                $dateReglement = Carbon::now();
            }

            // Créer le règlement
            $reglement = ReglementFournisseur::create([
                'numero_reglement' => ReglementFournisseur::genererNumeroReglement(),
                'date_reglement' => $dateReglement,
                'facture_id' => $facture->id,
                'fournisseur_id' => $facture->fournisseur_id,
                'montant' => $montant,
                'mode_paiement' => $mode,
                'reference' => $reference,
                'banque' => $banque,
                'numero_compte_bancaire' => $banque ? 'BJ' . rand(100000000, 999999999) : null,
                'observations' => 'Règlement de test créé par le seeder',
                'statut' => ReglementFournisseur::STATUT_VALIDE,
                'created_by' => 1, // Utilisateur par défaut
                'validated_by' => 1,
                'validated_at' => $dateReglement,
            ]);

            // Mettre à jour la facture
            $facture->enregistrerPaiement($montant);

            $this->command->info("Règlement créé: {$reglement->numero_reglement} - {$montant} XOF pour facture {$facture->numero_piece}");
        }

        $this->command->info('Seeding des règlements fournisseurs terminé!');
    }
}
