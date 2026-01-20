<?php

namespace App\Services;

use App\Models\CompteComptable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * Service pour la gestion du Plan Comptable OHADA
 */
class PlanComptableService
{
    /**
     * Durée du cache en secondes (1 heure)
     */
    private const CACHE_TTL = 3600;

    /**
     * Obtenir l'arbre complet d'une classe
     */
    public function getArbreClasse(int $classe): array
    {
        $cacheKey = "arbre_classe_{$classe}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($classe) {
            $racines = CompteComptable::classe($classe)
                ->racines()
                ->get();

            return $racines->map(fn($racine) => $this->construireArbreRecursif($racine))->toArray();
        });
    }

    /**
     * Construire l'arbre récursif d'un compte
     */
    private function construireArbreRecursif(CompteComptable $compte): array
    {
        $enfants = CompteComptable::whereRaw(
            'numero_compte LIKE ? AND LENGTH(numero_compte) = ?',
            [$compte->numero_compte . '%', strlen($compte->numero_compte) + 1]
        )->orderBy('numero_compte')->get();

        return [
            'compte' => [
                'id' => $compte->id,
                'numero_compte' => $compte->numero_compte,
                'libelle' => $compte->libelle,
                'niveau' => $compte->niveau,
                'type_compte' => $compte->type_compte,
                'utilisable' => $compte->peutEtreUtilise(),
            ],
            'enfants' => $enfants->map(fn($enfant) => $this->construireArbreRecursif($enfant))->toArray()
        ];
    }

    /**
     * Rechercher des comptes
     */
    public function rechercher(string $terme, ?int $classe = null, ?bool $utilisablesUniquement = false): Collection
    {
        $query = CompteComptable::recherche($terme);

        if ($classe !== null) {
            $query->classe($classe);
        }

        if ($utilisablesUniquement) {
            $query->utilisables();
        }

        return $query->orderBy('numero_compte')->get();
    }

    /**
     * Obtenir les comptes par type pour le bilan
     */
    public function getComptesBilan(): array
    {
        return [
            'actif' => [
                'immobilisations' => $this->getComptesUtilisablesClasse(CompteComptable::CLASSE_IMMOBILISATIONS),
                'stocks' => $this->getComptesUtilisablesClasse(CompteComptable::CLASSE_STOCKS),
                'creances' => CompteComptable::commencePar('41')->utilisables()->get(),
                'tresorerie' => $this->getComptesUtilisablesClasse(CompteComptable::CLASSE_TRESORERIE),
            ],
            'passif' => [
                'capitaux' => $this->getComptesUtilisablesClasse(CompteComptable::CLASSE_CAPITAUX),
                'dettes' => CompteComptable::commencePar('40')->utilisables()->get(),
            ],
        ];
    }

    /**
     * Obtenir les comptes pour le compte de résultat
     */
    public function getComptesResultat(): array
    {
        return [
            'charges' => $this->getArbreClasse(CompteComptable::CLASSE_CHARGES),
            'produits' => $this->getArbreClasse(CompteComptable::CLASSE_PRODUITS),
        ];
    }

    /**
     * Obtenir les comptes utilisables d'une classe
     */
    private function getComptesUtilisablesClasse(int $classe): Collection
    {
        return CompteComptable::classe($classe)
            ->utilisables()
            ->orderBy('numero_compte')
            ->get();
    }

    /**
     * Suggérer des comptes basés sur un pattern
     */
    public function suggererComptes(string $pattern, int $limit = 10): Collection
    {
        return CompteComptable::where('numero_compte', 'LIKE', $pattern . '%')
            ->orWhere('libelle', 'ILIKE', '%' . $pattern . '%')
            ->utilisables()
            ->orderBy('numero_compte')
            ->limit($limit)
            ->get();
    }

    /**
     * Valider une liste de numéros de comptes
     */
    public function validerComptes(array $numerosComptes): array
    {
        $resultats = [];

        foreach ($numerosComptes as $numeroCompte) {
            $resultats[$numeroCompte] = CompteComptable::validerNumeroCompte($numeroCompte);
        }

        return $resultats;
    }

    /**
     * Obtenir les comptes fréquemment utilisés
     */
    public function getComptesFrequents(int $limit = 20): Collection
    {
        // Cette méthode peut être enrichie avec des statistiques d'utilisation réelles
        // Pour l'instant, on retourne les comptes les plus courants

        $comptesFrequents = [
            '411', // Clients
            '401', // Fournisseurs
            '521', // Banques
            '571', // Caisse
            '445', // TVA
            '421', // Personnel - Avances
            '422', // Personnel - Rémunérations
            '60', // Achats
            '70', // Ventes
        ];

        return CompteComptable::whereIn('numero_compte', $comptesFrequents)
            ->orWhere(function ($query) use ($comptesFrequents) {
                foreach ($comptesFrequents as $compte) {
                    $query->orWhere('numero_compte', 'LIKE', $compte . '%');
                }
            })
            ->utilisables()
            ->orderBy('numero_compte')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtenir les statistiques du plan comptable
     */
    public function getStatistiques(): array
    {
        $cacheKey = 'stats_plan_comptable';

        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            return [
                'total' => CompteComptable::count(),
                'par_classe' => CompteComptable::select('classe', DB::raw('COUNT(*) as total'))
                    ->groupBy('classe')
                    ->orderBy('classe')
                    ->get()
                    ->mapWithKeys(fn($item) => [$item->classe => $item->total])
                    ->toArray(),
                'par_niveau' => CompteComptable::select('niveau', DB::raw('COUNT(*) as total'))
                    ->groupBy('niveau')
                    ->orderBy('niveau')
                    ->get()
                    ->mapWithKeys(fn($item) => [$item->niveau => $item->total])
                    ->toArray(),
                'par_type' => CompteComptable::select('type_compte', DB::raw('COUNT(*) as total'))
                    ->groupBy('type_compte')
                    ->get()
                    ->mapWithKeys(fn($item) => [$item->type_compte => $item->total])
                    ->toArray(),
                'utilisables' => CompteComptable::utilisables()->count(),
            ];
        });
    }

    /**
     * Obtenir un compte avec toute sa hiérarchie
     */
    public function getCompteAvecHierarchie(string $numeroCompte): ?array
    {
        $compte = CompteComptable::where('numero_compte', $numeroCompte)->first();

        if (!$compte) {
            return null;
        }

        return [
            'compte' => $compte->toApiArray(),
            'parent' => $compte->parent?->toApiArray(),
            'hierarchie' => $compte->hierarchie->map->toApiArray()->toArray(),
            'enfants' => $compte->enfants->map->toApiArray()->toArray(),
            'arbre' => $compte->getArbre(),
        ];
    }

    /**
     * Exporter le plan comptable en différents formats
     */
    public function exporter(string $format = 'json', ?int $classe = null): mixed
    {
        $query = CompteComptable::query()->orderBy('numero_compte');

        if ($classe !== null) {
            $query->classe($classe);
        }

        $comptes = $query->get();

        return match($format) {
            'json' => $comptes->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'array' => $comptes->toArray(),
            'csv' => $this->exporterCsv($comptes),
            'excel' => $this->exporterExcel($comptes),
            default => throw new \InvalidArgumentException("Format non supporté: {$format}"),
        };
    }

    /**
     * Exporter en CSV
     */
    private function exporterCsv(Collection $comptes): string
    {
        $csv = "Numéro;Libellé;Classe;Niveau;Type;Utilisable\n";

        foreach ($comptes as $compte) {
            $csv .= sprintf(
                "%s;%s;%d;%d;%s;%s\n",
                $compte->numero_compte,
                str_replace(';', ',', $compte->libelle),
                $compte->classe,
                $compte->niveau,
                $compte->type_compte,
                $compte->utilisable ? 'Oui' : 'Non'
            );
        }

        return $csv;
    }

    /**
     * Exporter en Excel (stub - nécessite une bibliothèque comme PhpSpreadsheet)
     */
    private function exporterExcel(Collection $comptes): array
    {
        // TODO: Implémenter avec PhpSpreadsheet si nécessaire
        return $comptes->map(function ($compte) {
            return [
                'Numéro' => $compte->numero_compte,
                'Libellé' => $compte->libelle,
                'Classe' => $compte->classe,
                'Niveau' => $compte->niveau,
                'Type' => $compte->type_compte,
                'Utilisable' => $compte->utilisable ? 'Oui' : 'Non',
            ];
        })->toArray();
    }

    /**
     * Nettoyer le cache du plan comptable
     */
    public function clearCache(): void
    {
        Cache::tags(['plan_comptable'])->flush();
    }

    /**
     * Importer des comptes personnalisés
     */
    public function importerComptesPersonnalises(array $comptes): array
    {
        $resultats = [
            'succes' => 0,
            'echecs' => 0,
            'erreurs' => [],
        ];

        DB::beginTransaction();

        try {
            foreach ($comptes as $compte) {
                // Valider les données
                if (!isset($compte['numero_compte']) || !isset($compte['libelle'])) {
                    $resultats['echecs']++;
                    $resultats['erreurs'][] = "Compte invalide: données manquantes";
                    continue;
                }

                // Créer ou mettre à jour
                CompteComptable::updateOrCreate(
                    ['numero_compte' => $compte['numero_compte']],
                    $compte
                );

                $resultats['succes']++;
            }

            DB::commit();
            $this->clearCache();

        } catch (\Exception $e) {
            DB::rollBack();
            $resultats['erreurs'][] = "Erreur lors de l'importation: " . $e->getMessage();
        }

        return $resultats;
    }
}
