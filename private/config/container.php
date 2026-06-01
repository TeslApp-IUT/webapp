<?php
/**
 * Câblage du conteneur d'injection de dépendances
 *
 * Déclare les « recettes » de construction des services de l'application
 * (controllers, et plus tard services, repositories, ports Tesla…) puis
 * retourne le conteneur prêt à l'emploi.
 *
 * Ce fichier est chargé par le front controller (www/index.php) après
 * l'autoload Composer, et grandit à chaque nouvelle dépendance à câbler.
 * Le câblage est explicite (pas d'autowiring) afin de rester lisible.
 */
declare(strict_types=1);

use Teslapp\Controllers\StaticPagesController;
use Teslapp\Utils\Container;

$container = new Container();

// Controllers
$container->set(
    StaticPagesController::class,
    static fn(): StaticPagesController => new StaticPagesController(),
);

return $container;
