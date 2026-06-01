<?php
/**
 * Configuration du routage de l'application
 *
 * Structure : 'URL' => [Nom du contrôleur, Nom de la méthode, Authentification requise (boolean)]
 *
 * - Le premier élément est le nom de la classe du contrôleur à instancier
 * - Le deuxième élément est le nom de la méthode à appeler sur ce contrôleur
 * - Le troisième élément indique si l'utilisateur doit être authentifié pour accéder à cette route
 */
declare(strict_types=1);

use Teslapp\Controllers\StaticPagesController;

return [
    // Routes des pages statiques accessibles à tous
    'site/home' => [StaticPagesController::class, 'home', false],
    'site/sitemap' => [StaticPagesController::class, 'sitemap', false],
    'site/legal' => [StaticPagesController::class, 'legal', false],
    'site/privacy' => [StaticPagesController::class, 'privacy', false],
    'error/404' => [StaticPagesController::class, 'notFound', false],
];
