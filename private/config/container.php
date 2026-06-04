<?php
/**
 * Dependency injection container configuration
 *
 * Declares the "recipes" for building the application's services
 * (controllers, and later services, repositories, Tesla ports, etc.) and then
 * returns the container ready for use.
 *
 * This file is loaded by the front controller (www/index.php) after
 * Composer autoloading, and grows with each new dependency to be wired.
 * The wiring is explicit (no autowiring) to ensure readability.
 */
declare(strict_types=1);

use Teslapp\Controllers\AuthController;
use Teslapp\Controllers\CallbackAuthController;
use Teslapp\Controllers\StaticPagesController;
use Teslapp\Utils\Container;

$container = new Container();

// Controllers
$container->set(
    StaticPagesController::class,
    static fn(): StaticPagesController => new StaticPagesController(),
);

$container->set(
    AuthController::class,
    static fn(): AuthController => new AuthController(),
);

$container->set(
    CallbackAuthController::class,
    static fn(): CallbackAuthController => new CallbackAuthController()
);

return $container;
