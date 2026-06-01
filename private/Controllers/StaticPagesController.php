<?php

declare(strict_types=1);

namespace Teslapp\Controllers;

/**
 * Contrôleur des pages statiques
 *
 * Ce contrôleur gère l'affichage de toutes les pages statiques de l'application
 * qui ne nécessitent pas de traitement particulier ni d'authentification.
 *
 * Note : les templates de vues vivent encore dans private/modules/views/
 * (ils seront déplacés vers private/Views/ au lot layout factorisé).
 */
final class StaticPagesController
{
    /**
     * Affiche la page d'accueil
     *
     * @return void
     */
    public function home(): void
    {
        require __DIR__ . '/../modules/views/home.php';
    }

    /**
     * Affiche le plan du site
     *
     * @return void
     */
    public function sitemap(): void
    {
        require __DIR__ . '/../modules/views/sitemap.php';
    }

    /**
     * Affiche les mentions légales
     *
     * @return void
     */
    public function legal(): void
    {
        require __DIR__ . '/../modules/views/legal.php';
    }

    /**
     * Affiche la politique de confidentialité
     *
     * @return void
     */
    public function privacy(): void
    {
        require __DIR__ . '/../modules/views/privacy.php';
    }

    /**
     * Affiche la page d'erreur 404
     *
     * @return void
     */
    public function notFound(): void
    {
        require __DIR__ . '/../modules/views/not-found.php';
    }
}
