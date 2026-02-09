<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PlanComptableOhadaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Charge le Plan Comptable OHADA depuis le fichier Excel
     */
    public function run(): void
    {
        $filePath = base_path('docs/Plan-comptable-général-1.xlsx');

        if (!file_exists($filePath)) {
            $this->command->error("Fichier Excel non trouvé: $filePath");
            return;
        }

        $this->command->info('Chargement du fichier Excel...');

        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();

        $this->command->info("Nombre de lignes: $highestRow");

        $comptes = [];
        $skipped = 0;

        for ($row = 6; $row <= $highestRow; $row++) {
            $numeroCompte = $sheet->getCell('B' . $row)->getValue();
            $libelle = $sheet->getCell('C' . $row)->getValue();

            // Skip if no numeric account number
            if ($numeroCompte === null || !is_numeric($numeroCompte)) {
                $skipped++;
                continue;
            }

            // Convert to string to preserve leading zeros (if any)
            $numeroCompte = (string) $numeroCompte;

            // Skip if no libelle
            if (empty(trim((string) $libelle))) {
                $skipped++;
                continue;
            }

            // Determine classe (first digit)
            $classe = (int) substr($numeroCompte, 0, 1);

            // Determine niveau (based on account number length)
            $length = strlen($numeroCompte);
            $niveau = match($length) {
                2 => 1,      // 10, 11, 12...
                3 => 2,      // 101, 102, 103...
                4 => 3,      // 1011, 1012...
                5 => 4,      // 10111, 10112...
                default => min($length - 1, 5)  // cap at 5
            };

            // Determine type_compte based on classe
            $typeCompte = $this->getTypeCompte($classe);

            // Determine if account is directly usable (usually leaf accounts with 4+ digits)
            $utilisable = $length >= 4;

            $comptes[] = [
                'numero_compte' => $numeroCompte,
                'libelle' => trim((string) $libelle),
                'classe' => $classe,
                'niveau' => $niveau,
                'type_compte' => $typeCompte,
                'utilisable' => $utilisable,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $this->command->info("Comptes trouvés: " . count($comptes));
        $this->command->info("Lignes ignorées: $skipped");

        if (count($comptes) > 0) {
            // Clear existing data
            DB::table('plan_comptable_ohada')->truncate();

            // Insert in chunks
            $chunks = array_chunk($comptes, 100);
            $bar = $this->command->getOutput()->createProgressBar(count($chunks));

            foreach ($chunks as $chunk) {
                DB::table('plan_comptable_ohada')->insert($chunk);
                $bar->advance();
            }

            $bar->finish();
            $this->command->newLine();
            $this->command->info('Plan Comptable OHADA chargé avec succès!');

            // Show statistics
            $this->afficherStatistiques();
        }
    }

    /**
     * Determine account type based on class
     */
    private function getTypeCompte(int $classe): string
    {
        return match($classe) {
            1 => 'PASSIF',      // Comptes de capitaux
            2 => 'ACTIF',       // Comptes d'immobilisations
            3 => 'ACTIF',       // Comptes de stocks
            4 => 'ACTIF',       // Comptes de tiers (mixed but default to ACTIF)
            5 => 'ACTIF',       // Comptes de trésorerie
            6 => 'CHARGE',      // Comptes de charges
            7 => 'PRODUIT',     // Comptes de produits
            8 => 'SPECIAL',     // Comptes des autres charges et autres produits
            9 => 'SPECIAL',     // Comptes des engagements hors bilan
            default => 'SPECIAL'
        };
    }

    /**
     * Display import statistics
     */
    private function afficherStatistiques(): void
    {
        $this->command->newLine();
        $this->command->info('=== Statistiques ===');

        // By class
        $byClasse = DB::table('plan_comptable_ohada')
            ->select('classe', DB::raw('count(*) as total'))
            ->groupBy('classe')
            ->orderBy('classe')
            ->get();

        foreach ($byClasse as $row) {
            $this->command->line("Classe {$row->classe}: {$row->total} comptes");
        }

        // By type
        $byType = DB::table('plan_comptable_ohada')
            ->select('type_compte', DB::raw('count(*) as total'))
            ->groupBy('type_compte')
            ->get();

        $this->command->newLine();
        foreach ($byType as $row) {
            $this->command->line("{$row->type_compte}: {$row->total} comptes");
        }

        // Total
        $total = DB::table('plan_comptable_ohada')->count();
        $this->command->newLine();
        $this->command->info("Total: {$total} comptes");
    }
}
