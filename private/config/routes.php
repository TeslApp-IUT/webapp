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

return [
    // Routes des pages statiques accessibles à tous
    'site/home' => ['StaticPagesController', 'home', false],
    'site/sitemap' => ['StaticPagesController', 'sitemap', false],
    'site/legal' => ['StaticPagesController', 'legal', false],
    'site/privacy' => ['StaticPagesController', 'privacy', false],
    'error/404' => ['StaticPagesController', 'notFound', false],
];
