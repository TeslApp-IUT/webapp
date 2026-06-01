<?php

declare(strict_types=1);

/**
 * Fonctions utilitaires globales de Tesla App.
 *
 * Chargées via l'autoload Composer (clé "files"), donc disponibles partout
 * sans `use`. À réserver aux helpers transverses très courts (échappement, …) ;
 * toute logique structurée passe par une classe PSR-4 de Teslapp\Utils.
 */

if (!function_exists('e')) {
    /**
     * Échappe une chaîne pour une insertion sûre dans du HTML (anti-XSS).
     *
     * Flags : ENT_QUOTES (échappe " et ' — sûr dans les attributs),
     * ENT_SUBSTITUTE (UTF-8 invalide → U+FFFD au lieu d'une chaîne vide),
     * ENT_HTML5 (jeu d'entités HTML5).
     *
     * @param string|null $value Valeur à échapper (null traité comme '')
     * @return string La valeur échappée
     */
    function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }
}
