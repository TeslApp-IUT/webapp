<?php

declare(strict_types=1);

namespace Teslapp\Controllers;

/**
 * Contrôleur des pages statiques
 *
 * Ce contrôleur gère l'affichage de toutes les pages statiques de l'application
 * qui ne nécessitent pas de traitement particulier ni d'authentification.
 *
 * Note : les templates de vues vivent dans private/Views/static/ et sont
 * rendus via le layout factorisé private/Views/layout.php.
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
        require_once __DIR__ . '/../Views/static/home.php';
    }

    /**
     * Affiche le plan du site
     *
     * @return void
     */
    public function sitemap(): void
    {
        require_once __DIR__ . '/../Views/static/sitemap.php';
    }

    /**
     * Affiche les mentions légales
     *
     * @return void
     */
    public function legal(): void
    {
        require_once __DIR__ . '/../Views/static/legal.php';
    }

    /**
     * Affiche la politique de confidentialité
     *
     * @return void
     */
    public function privacy(): void
    {
        require_once __DIR__ . '/../Views/static/privacy.php';
    }

    /**
     * Affiche la page d'erreur 404
     *
     * @return void
     */
    public function notFound(): void
    {
        require_once __DIR__ . '/../Views/static/not-found.php';
    }
}
