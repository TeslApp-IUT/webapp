<?php
/**
 * Contrôleur des pages statiques
 *
 * Ce contrôleur gère l'affichage de toutes les pages statiques de l'application
 * qui ne nécessitent pas de traitement particulier ni d'authentification.
 */

/**
 * Class StaticPagesController
 *
 * Gère les pages statiques suivantes :
 * - Page d'accueil
 * - Plan du site
 * - Mentions légales
 * - Politique de confidentialité
 * - Page d'erreur 404
 */
final class StaticPagesController
{
    /**
     * Affiche la page d'accueil
     *
     * Page de bienvenue présentant l'application MedBoard
     *
     * @return void
     */
    public function home(): void
    {
        require __DIR__ . '/../views/home.php';
    }

    /**
     * Affiche le plan du site
     *
     * Liste tous les liens et pages disponibles dans l'application
     *
     * @return void
     */
    public function sitemap(): void
    {
        require __DIR__ . '/../views/sitemap.php';
    }

    /**
     * Affiche les mentions légales
     *
     * Informations légales concernant l'exploitation du site
     *
     * @return void
     */
    public function legal(): void
    {
        require __DIR__ . '/../views/legal.php';
    }

    /**
     * Affiche la politique de confidentialité
     *
     * Informations sur la collecte et l'utilisation des données personnelles
     *
     * @return void
     */
    public function privacy(): void
    {
        require __DIR__ . '/../views/privacy.php';
    }

    /**
     * Affiche la page d'erreur 404
     *
     * Page affichée lorsqu'une route n'existe pas
     *
     * @return void
     */
    public function notFound(): void
    {
        require __DIR__ . '/../views/not-found.php';
    }
}
