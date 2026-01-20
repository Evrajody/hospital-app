<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class PlanComptableOhadaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Importation du Plan Comptable OHADA...');

        // Désactiver temporairement les contraintes de clés étrangères
        DB::statement('SET CONSTRAINTS ALL DEFERRED');

        // Vider la table si elle existe déjà
        DB::table('plan_comptable_ohada')->truncate();

        // Chemin vers le fichier SQL
        $sqlFile = base_path('plan_comptable_ohada.sql');

        if (!File::exists($sqlFile)) {
            $this->command->error("Le fichier plan_comptable_ohada.sql est introuvable à la racine du projet!");
            $this->command->error("Veuillez vous assurer que le fichier existe: {$sqlFile}");
            return;
        }

        $this->command->info("Lecture du fichier SQL: {$sqlFile}");

        // Lire et exécuter le fichier SQL
        $sql = File::get($sqlFile);

        // Exécuter le SQL
        try {
            DB::unprepared($sql);

            // Compter les comptes importés
            $count = DB::table('plan_comptable_ohada')->count();

            $this->command->info("✓ {$count} comptes comptables importés avec succès!");

            // Afficher les statistiques par classe
            $this->afficherStatistiques();

        } catch (\Exception $e) {
            $this->command->error("Erreur lors de l'importation: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Afficher les statistiques d'importation
     */
    private function afficherStatistiques(): void
    {
        $this->command->info("\n=== Statistiques du Plan Comptable OHADA ===\n");

        $stats = DB::table('plan_comptable_ohada')
            ->select('classe', DB::raw('COUNT(*) as total'))
            ->groupBy('classe')
            ->orderBy('classe')
            ->get();

        $classesLibelles = [
            1 => 'Ressources durables (Capitaux)',
            2 => 'Actif immobilisé',
            3 => 'Stocks',
            4 => 'Tiers',
            5 => 'Trésorerie',
            6 => 'Charges',
            7 => 'Produits',
            8 => 'H.A.O. (Hors Activités Ordinaires)',
            9 => 'Comptabilité analytique',
        ];

        foreach ($stats as $stat) {
            $libelle = $classesLibelles[$stat->classe] ?? "Classe {$stat->classe}";
            $this->command->info(sprintf(
                "Classe %d (%s): %d comptes",
                $stat->classe,
                $libelle,
                $stat->total
            ));
        }

        // Statistiques par niveau
        $this->command->info("\n=== Par niveau hiérarchique ===\n");

        $niveaux = DB::table('plan_comptable_ohada')
            ->select('niveau', DB::raw('COUNT(*) as total'))
            ->groupBy('niveau')
            ->orderBy('niveau')
            ->get();

        foreach ($niveaux as $niveau) {
            $this->command->info(sprintf(
                "Niveau %d: %d comptes",
                $niveau->niveau,
                $niveau->total
            ));
        }

        // Comptes utilisables
        $utilisables = DB::table('plan_comptable_ohada')
            ->where('utilisable', true)
            ->whereRaw('NOT EXISTS (
                SELECT 1 FROM plan_comptable_ohada c2
                WHERE c2.numero_compte LIKE CONCAT(plan_comptable_ohada.numero_compte, \'%\')
                AND LENGTH(c2.numero_compte) > LENGTH(plan_comptable_ohada.numero_compte)
            )')
            ->count();

        $this->command->info(sprintf(
            "\nComptes utilisables (feuilles): %d",
            $utilisables
        ));

        $this->command->info("\n==========================================\n");
    }
}
