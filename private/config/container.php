<?php
/**
 * DI container: explicit build recipes for the application services.
 * Loaded by www/index.php after Composer autoloading.
 */
declare(strict_types=1);

use Teslapp\Controllers\Auth\AuthCallbackController;
use Teslapp\Controllers\Auth\AuthController;
use Teslapp\Controllers\DashboardController;
use Teslapp\Controllers\StaticPagesController;
use Teslapp\Models\Database;
use Teslapp\Models\Shared\TeslaApi\TeslaStateClient;
use Teslapp\Models\Shared\TeslaApi\VehicleStateClient;
use Teslapp\Models\Vehicle\TeslaModelRepository;
use Teslapp\Models\Vehicle\TeslaModelRepositoryInterface;
use Teslapp\Models\Vehicle\VehicleRepository;
use Teslapp\Models\Vehicle\VehicleRepositoryInterface;
use Teslapp\Utils\Container;

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
    DashboardController::class,
    static fn(): DashboardController => new DashboardController(Database::pdo()),
);

// VehicleService, VehicleController and their routes: pending the OAuth AuthService (token provider).

$container->set(
    AuthController::class,
    static fn(): AuthController => new AuthController());

$container->set(
    AuthCallbackController::class,
    static fn(): AuthCallbackController => new AuthCallbackController(),
);

return $container;
