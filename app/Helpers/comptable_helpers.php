<?php

use App\Models\CompteComptable;
use App\Services\PlanComptableService;

if (!function_exists('compte')) {
    /**
     * Obtenir un compte comptable par son numéro
     *
     * @param string $numeroCompte
     * @return CompteComptable|null
     */
    function compte(string $numeroCompte): ?CompteComptable
    {
        return CompteComptable::where('numero_compte', $numeroCompte)->first();
    }
}

if (!function_exists('comptes_classe')) {
    /**
     * Obtenir tous les comptes d'une classe
     *
     * @param int $classe
     * @return \Illuminate\Database\Eloquent\Collection
     */
    function comptes_classe(int $classe)
    {
        return CompteComptable::classe($classe)->get();
    }
}

if (!function_exists('comptes_utilisables')) {
    /**
     * Obtenir tous les comptes utilisables d'une classe
     *
     * @param int|null $classe
     * @return \Illuminate\Database\Eloquent\Collection
     */
    function comptes_utilisables(?int $classe = null)
    {
        $query = CompteComptable::utilisables();

        if ($classe !== null) {
            $query->classe($classe);
        }

        return $query->get();
    }
}

if (!function_exists('comptes_clients')) {
    /**
     * Obtenir tous les comptes clients
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    function comptes_clients()
    {
        return CompteComptable::clients();
    }
}

if (!function_exists('comptes_fournisseurs')) {
    /**
     * Obtenir tous les comptes fournisseurs
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    function comptes_fournisseurs()
    {
        return CompteComptable::fournisseurs();
    }
}

if (!function_exists('comptes_banques')) {
    /**
     * Obtenir tous les comptes banques
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    function comptes_banques()
    {
        return CompteComptable::banques();
    }
}

if (!function_exists('comptes_caisses')) {
    /**
     * Obtenir tous les comptes caisses
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    function comptes_caisses()
    {
        return CompteComptable::caisses();
    }
}

if (!function_exists('valider_compte')) {
    /**
     * Valider un numéro de compte
     *
     * @param string $numeroCompte
     * @return array
     */
    function valider_compte(string $numeroCompte): array
    {
        return CompteComptable::validerNumeroCompte($numeroCompte);
    }
}

if (!function_exists('rechercher_comptes')) {
    /**
     * Rechercher des comptes par terme
     *
     * @param string $terme
     * @return \Illuminate\Database\Eloquent\Collection
     */
    function rechercher_comptes(string $terme)
    {
        return CompteComptable::rechercherComptes($terme);
    }
}

if (!function_exists('plan_comptable')) {
    /**
     * Obtenir une instance du service Plan Comptable
     *
     * @return PlanComptableService
     */
    function plan_comptable(): PlanComptableService
    {
        return app(PlanComptableService::class);
    }
}

if (!function_exists('arbre_comptable')) {
    /**
     * Obtenir l'arbre comptable d'une classe
     *
     * @param int $classe
     * @return array
     */
    function arbre_comptable(int $classe): array
    {
        return plan_comptable()->getArbreClasse($classe);
    }
}

if (!function_exists('formater_compte')) {
    /**
     * Formater un numéro de compte avec son libellé
     *
     * @param string|CompteComptable $compte
     * @param string $format 'court'|'long'|'complet'
     * @return string
     */
    function formater_compte($compte, string $format = 'long'): string
    {
        if (is_string($compte)) {
            $compte = CompteComptable::where('numero_compte', $compte)->first();
        }

        if (!$compte instanceof CompteComptable) {
            return '';
        }

        return match($format) {
            'court' => $compte->getFormatCourt(),
            'long' => $compte->getFormatLong(),
            'complet' => $compte->getFormatComplet(),
            default => $compte->getFormatLong(),
        };
    }
}

if (!function_exists('est_compte_debit')) {
    /**
     * Vérifier si un compte est normalement débiteur
     *
     * @param string|CompteComptable $compte
     * @return bool
     */
    function est_compte_debit($compte): bool
    {
        if (is_string($compte)) {
            $compte = CompteComptable::where('numero_compte', $compte)->first();
        }

        if (!$compte instanceof CompteComptable) {
            return false;
        }

        // Les comptes d'actif et de charges sont normalement débiteurs
        return in_array($compte->type_compte, [
            CompteComptable::TYPE_ACTIF,
            CompteComptable::TYPE_CHARGE,
        ]);
    }
}

if (!function_exists('est_compte_credit')) {
    /**
     * Vérifier si un compte est normalement créditeur
     *
     * @param string|CompteComptable $compte
     * @return bool
     */
    function est_compte_credit($compte): bool
    {
        if (is_string($compte)) {
            $compte = CompteComptable::where('numero_compte', $compte)->first();
        }

        if (!$compte instanceof CompteComptable) {
            return false;
        }

        // Les comptes de passif et de produits sont normalement créditeurs
        return in_array($compte->type_compte, [
            CompteComptable::TYPE_PASSIF,
            CompteComptable::TYPE_PRODUIT,
        ]);
    }
}

if (!function_exists('get_classe_libelle')) {
    /**
     * Obtenir le libellé d'une classe
     *
     * @param int $classe
     * @return string
     */
    function get_classe_libelle(int $classe): string
    {
        return match($classe) {
            1 => 'Ressources durables',
            2 => 'Actif immobilisé',
            3 => 'Stocks',
            4 => 'Tiers',
            5 => 'Trésorerie',
            6 => 'Charges',
            7 => 'Produits',
            8 => 'H.A.O.',
            9 => 'Comptabilité analytique',
            default => 'Classe inconnue',
        };
    }
}

if (!function_exists('comptes_select_options')) {
    /**
     * Obtenir les comptes formatés pour un select HTML
     *
     * @param int|null $classe
     * @param bool $utilisablesUniquement
     * @return array
     */
    function comptes_select_options(?int $classe = null, bool $utilisablesUniquement = true): array
    {
        $query = CompteComptable::query();

        if ($classe !== null) {
            $query->classe($classe);
        }

        if ($utilisablesUniquement) {
            $query->utilisables();
        }

        return $query->orderBy('numero_compte')
            ->get()
            ->map(fn($compte) => $compte->toSelectOption())
            ->toArray();
    }
}

if (!function_exists('stats_plan_comptable')) {
    /**
     * Obtenir les statistiques du plan comptable
     *
     * @return array
     */
    function stats_plan_comptable(): array
    {
        return plan_comptable()->getStatistiques();
    }
}
