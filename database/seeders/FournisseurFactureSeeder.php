<?php

namespace Database\Seeders;

use App\Models\Fournisseur;
use App\Models\FactureFournisseur;
use App\Models\CompteComptable;
use Illuminate\Database\Seeder;

class FournisseurFactureSeeder extends Seeder
{
    /**
     * Seed des fournisseurs et factures pour les tests
     */
    public function run(): void
    {
        $this->command->info('Création des fournisseurs de test...');

        // Récupérer un compte fournisseur (401xxx)
        $compteFournisseur = CompteComptable::where('numero_compte', 'LIKE', '401%')
            ->first();

        if (!$compteFournisseur) {
            $compteFournisseur = CompteComptable::create([
                'numero_compte' => '401100',
                'libelle' => 'Fournisseurs divers',
                'classe' => 4,
                'niveau' => 4,
                'type_compte' => 'PASSIF',
            ]);
        }

        // Créer des fournisseurs
        $fournisseurs = [
            [
                'nom' => 'Pharmacie Centrale du Bénin',
                'type_fournisseur' => 'medicaments',
                'contact' => 'M. Adamou Koffi',
                'fonction_contact' => 'Directeur Commercial',
                'telephone' => '+229 21 30 45 67',
                'telephone_secondaire' => '+229 97 00 00 01',
                'email' => 'contact@pcb.bj',
                'adresse' => 'Boulevard de la Marina, Cotonou',
                'ville' => 'Cotonou',
                'pays' => 'BJ',
                'ifu' => '3201900001234',
                'rccm' => 'RB/COT/19-A-12345',
                'regime_fiscal' => 'reel_normal',
                'taux_aib' => '1',
                'assujetti_tva' => true,
                'delai_paiement' => 30,
                'mode_paiement_prefere' => 'virement',
                'status' => 'actif',
            ],
            [
                'nom' => 'SOBEMAP Matériel Médical',
                'type_fournisseur' => 'equipements',
                'contact' => 'Mme Adjoua Marie',
                'fonction_contact' => 'Responsable Ventes',
                'telephone' => '+229 21 31 22 33',
                'email' => 'commercial@sobemap.bj',
                'adresse' => 'Zone Industrielle, PK8',
                'ville' => 'Cotonou',
                'pays' => 'BJ',
                'ifu' => '3201800005678',
                'rccm' => 'RB/COT/18-B-67890',
                'regime_fiscal' => 'reel_normal',
                'taux_aib' => '3',
                'assujetti_tva' => true,
                'delai_paiement' => 45,
                'mode_paiement_prefere' => 'cheque',
                'status' => 'actif',
            ],
            [
                'nom' => 'SONEB (Eau)',
                'type_fournisseur' => 'services',
                'contact' => 'Service Client',
                'telephone' => '+229 21 30 00 00',
                'email' => 'clients@soneb.bj',
                'adresse' => 'Rue du Commerce',
                'ville' => 'Cotonou',
                'pays' => 'BJ',
                'ifu' => '3200100012345',
                'regime_fiscal' => 'reel_normal',
                'taux_aib' => '0',
                'assujetti_tva' => true,
                'delai_paiement' => 15,
                'mode_paiement_prefere' => 'virement',
                'status' => 'actif',
            ],
            [
                'nom' => 'SBEE (Electricité)',
                'type_fournisseur' => 'services',
                'contact' => 'Service Abonnés',
                'telephone' => '+229 21 31 00 00',
                'email' => 'abonnes@sbee.bj',
                'adresse' => 'Avenue Clozel',
                'ville' => 'Cotonou',
                'pays' => 'BJ',
                'ifu' => '3200100054321',
                'regime_fiscal' => 'reel_normal',
                'taux_aib' => '0',
                'assujetti_tva' => true,
                'delai_paiement' => 15,
                'mode_paiement_prefere' => 'virement',
                'status' => 'actif',
            ],
            [
                'nom' => 'Fournitures Médicales SANTE+',
                'type_fournisseur' => 'consommables',
                'contact' => 'M. Dossou Pierre',
                'fonction_contact' => 'Gérant',
                'telephone' => '+229 97 88 77 66',
                'email' => 'contact@santeplus.bj',
                'adresse' => 'Quartier Gbédjromédé',
                'ville' => 'Cotonou',
                'pays' => 'BJ',
                'ifu' => '3202000098765',
                'rccm' => 'RB/COT/20-C-98765',
                'regime_fiscal' => 'reel_simplifie',
                'taux_aib' => '1',
                'assujetti_tva' => true,
                'delai_paiement' => 30,
                'mode_paiement_prefere' => 'especes',
                'status' => 'actif',
            ],
        ];

        $fournisseurIds = [];
        foreach ($fournisseurs as $data) {
            $data['compte_comptable_id'] = $compteFournisseur->id;
            $fournisseur = Fournisseur::create($data);
            $fournisseurIds[] = $fournisseur->id;
            $this->command->info("  - Fournisseur créé: {$fournisseur->nom}");
        }

        $this->command->info('Création des factures de test...');

        // Récupérer un compte de charges (6xxx)
        $compteCharge = CompteComptable::where('numero_compte', 'LIKE', '6%')
            ->first();

        // Créer des factures
        $factures = [
            [
                'numero_piece' => 'PC/026/0001',
                'date' => '2026-01-10',
                'reference_facture' => 'BC-2026-001',
                'fournisseur_id' => $fournisseurIds[0],
                'libelle' => 'Achat de médicaments - Paracétamol et Amoxicilline',
                'montant_facture' => 5000000,
                'montant_mo' => 5000000,
                'avoir' => 0,
                'type_reduction' => 'aib',
                'taux' => 1,
                'assujetti_tva' => true,
                'taux_tva' => 18,
                'date_echeance' => '2026-02-10',
                'observations' => 'Commande urgente - Rupture de stock',
                'statut' => 'validee',
                'montant_paye' => 0,
            ],
            [
                'numero_piece' => 'PC/026/0002',
                'date' => '2026-01-15',
                'reference_facture' => 'BC-2026-002',
                'fournisseur_id' => $fournisseurIds[1],
                'libelle' => 'Acquisition de matériel - Stéthoscopes et tensiomètres',
                'montant_facture' => 3000000,
                'montant_mo' => 3000000,
                'avoir' => 0,
                'type_reduction' => 'aib',
                'taux' => 3,
                'assujetti_tva' => true,
                'taux_tva' => 18,
                'date_echeance' => '2026-03-01',
                'observations' => null,
                'statut' => 'partiellement_payee',
                'montant_paye' => 2000000,
            ],
            [
                'numero_piece' => 'PC/026/0003',
                'date' => '2026-01-20',
                'reference_facture' => 'FACT-EAU-01-2026',
                'fournisseur_id' => $fournisseurIds[2],
                'libelle' => 'Consommation eau - Janvier 2026',
                'montant_facture' => 150000,
                'montant_mo' => 0,
                'avoir' => 0,
                'type_reduction' => null,
                'taux' => 0,
                'assujetti_tva' => true,
                'taux_tva' => 18,
                'date_echeance' => '2026-02-05',
                'observations' => null,
                'statut' => 'payee',
                'montant_paye' => 177000,
            ],
            [
                'numero_piece' => 'PC/026/0004',
                'date' => '2026-01-22',
                'reference_facture' => 'FACT-ELEC-01-2026',
                'fournisseur_id' => $fournisseurIds[3],
                'libelle' => 'Consommation électricité - Janvier 2026',
                'montant_facture' => 450000,
                'montant_mo' => 0,
                'avoir' => 0,
                'type_reduction' => null,
                'taux' => 0,
                'assujetti_tva' => true,
                'taux_tva' => 18,
                'date_echeance' => '2026-02-07',
                'observations' => null,
                'statut' => 'validee',
                'montant_paye' => 0,
            ],
            [
                'numero_piece' => 'PC/026/0005',
                'date' => '2026-01-25',
                'reference_facture' => 'BC-2026-003',
                'fournisseur_id' => $fournisseurIds[4],
                'libelle' => 'Fournitures médicales - Gants, seringues, compresses',
                'montant_facture' => 800000,
                'montant_mo' => 800000,
                'avoir' => 50000,
                'type_reduction' => 'aib',
                'taux' => 1,
                'assujetti_tva' => true,
                'taux_tva' => 18,
                'date_echeance' => '2026-02-25',
                'observations' => 'Avoir pour produits périmés retournés',
                'statut' => 'brouillon',
                'montant_paye' => 0,
            ],
            [
                'numero_piece' => 'PC/026/0006',
                'date' => '2026-01-28',
                'reference_facture' => 'BC-2026-004',
                'fournisseur_id' => $fournisseurIds[0],
                'libelle' => 'Achat de vaccins - Campagne de vaccination',
                'montant_facture' => 2500000,
                'montant_mo' => 2500000,
                'avoir' => 0,
                'type_reduction' => 'aib',
                'taux' => 1,
                'assujetti_tva' => true,
                'taux_tva' => 18,
                'date_echeance' => '2026-02-28',
                'observations' => 'Commande pour campagne de vaccination enfants',
                'statut' => 'validee',
                'montant_paye' => 0,
            ],
        ];

        foreach ($factures as $data) {
            if ($compteCharge) {
                $data['imputation_id'] = $compteCharge->id;
            }
            $facture = FactureFournisseur::create($data);
            $this->command->info("  - Facture créée: {$facture->numero_piece} ({$facture->libelle})");
        }

        $this->command->newLine();
        $this->command->info('Données de test créées avec succès !');
        $this->command->info('  - ' . count($fournisseurs) . ' fournisseurs');
        $this->command->info('  - ' . count($factures) . ' factures');
    }
}
