<?php
/**
 * DI container: explicit build recipes for the application services.
 * Loaded by www/index.php after Composer autoloading.
 */
declare(strict_types=1);

use Teslapp\Controllers\Auth\AuthCallbackController;
use Teslapp\Controllers\Auth\AuthSignUpController;
use Teslapp\Controllers\Auth\AuthController;
use Teslapp\Controllers\StaticPagesController;
use Teslapp\Controllers\DashboardController;
use Teslapp\Controllers\VehicleController;
use Teslapp\Models\Auth\RememberTokenRepository;
use Teslapp\Models\Database;
use Teslapp\Models\Shared\TeslaApi\TeslaStateClient;
use Teslapp\Models\Shared\TeslaApi\VehicleStateClient;
use Teslapp\Models\Vehicle\TeslaModelRepository;
use Teslapp\Models\Vehicle\TeslaModelRepositoryInterface;
use Teslapp\Models\Vehicle\VehicleRepository;
use Teslapp\Models\Vehicle\VehicleRepositoryInterface;
use Teslapp\Models\Vehicle\VehicleService;
use Teslapp\Utils\Container;
use Teslapp\Utils\RememberToken;

$container = new Container();

// Controllers
$container->set(
    StaticPagesController::class,
    static fn(): StaticPagesController => new StaticPagesController(),
);

$container->set(
    VehicleRepositoryInterface::class,
    static fn(): VehicleRepositoryInterface => new VehicleRepository(Database::pdo()),
);
$container->set(
    TeslaModelRepositoryInterface::class,
    static fn(): TeslaModelRepositoryInterface => new TeslaModelRepository(Database::pdo()),
);
$container->set(
    VehicleStateClient::class,
    static fn(): VehicleStateClient => new TeslaStateClient(),
);
$container->set(
    VehicleService::class,
    static fn(Container $c): VehicleService => new VehicleService(
        $c->get(VehicleStateClient::class),
        $c->get(VehicleRepositoryInterface::class),
        $c->get(TeslaModelRepositoryInterface::class),
    ),
);
$container->set(
    VehicleController::class,
    static fn(Container $c): VehicleController => new VehicleController(
        $c->get(VehicleService::class),
    ),
);
$container->set(
    DashboardController::class,
    static fn(): DashboardController => new DashboardController(Database::pdo()),
);
$container->set(AuthController::class, static fn(): AuthController => new AuthController());

$container->set(
    AuthCallbackController::class,
    static fn(): AuthCallbackController => new AuthCallbackController(),
);

$container->set(
    AuthSignUpController::class,
    static fn(): AuthSignUpController => new AuthSignUpController(Database::pdo()),
);

// Remember-me
$container->set(
    RememberTokenRepository::class,
    static fn(): RememberTokenRepository => new RememberTokenRepository(Database::pdo()),
);
$container->set(
    RememberToken::class,
    static fn(Container $c): RememberToken => new RememberToken(
        $c->get(RememberTokenRepository::class),
    ),
);

return $container;
