<?php

namespace App\Support;

/**
 * Ajustement du plafond mémoire PHP pour les traitements lourds (rendu dompdf).
 *
 * `ini_set('memory_limit', ...)` en dur est un piège : en production le conteneur
 * tourne avec `memory_limit=-1` (cf. docker/php/production-overrides.ini) et un
 * `ini_set('2048M')` **abaisserait** la limite. On ne relève donc jamais vers le bas.
 */
class PhpMemory
{
    /**
     * Relève le plafond mémoire jusqu'à $limit (ex. « 2048M »), jamais en dessous
     * de la valeur courante. Sans effet si la mémoire est déjà illimitée.
     */
    public static function raiseTo(string $limit): void
    {
        $courant = (string) ini_get('memory_limit');

        if ($courant === '-1') {
            return; // déjà illimité
        }

        if (self::enOctets($limit) > self::enOctets($courant)) {
            @ini_set('memory_limit', $limit);
        }
    }

    /**
     * Convertit une valeur de type ini (« 512M », « 1G », « -1 ») en octets.
     * `-1` (illimité) est traduit par PHP_INT_MAX pour rester comparable.
     */
    public static function enOctets(string $valeur): int
    {
        $valeur = trim($valeur);

        if ($valeur === '' || $valeur === '-1') {
            return $valeur === '-1' ? PHP_INT_MAX : 0;
        }

        $nombre = (int) $valeur;
        $suffixe = strtolower(substr($valeur, -1));

        return match ($suffixe) {
            'g' => $nombre * 1024 * 1024 * 1024,
            'm' => $nombre * 1024 * 1024,
            'k' => $nombre * 1024,
            default => $nombre,
        };
    }
}
