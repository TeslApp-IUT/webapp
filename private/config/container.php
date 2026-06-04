<?php
/**
 * DI container: explicit build recipes for the application services.
 * Loaded by www/index.php after Composer autoloading.
 */
declare(strict_types=1);

use Teslapp\Controllers\StaticPagesController;
use Teslapp\Controllers\DashboardController;
use Teslapp\Controllers\VehicleController;
use Teslapp\Models\Database;
use Teslapp\Models\Shared\TeslaApi\TeslaApiClient;
use Teslapp\Models\Shared\TeslaApi\VehicleStateClient;
use Teslapp\Models\Vehicle\TeslaModelRepository;
use Teslapp\Models\Vehicle\TeslaModelRepositoryInterface;
use Teslapp\Models\Vehicle\VehicleRepository;
use Teslapp\Models\Vehicle\VehicleRepositoryInterface;
use Teslapp\Models\Vehicle\VehicleService;
use Teslapp\Utils\Container;

$container = new Container();

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
    static fn(): VehicleStateClient => new TeslaApiClient(
        getenv('TESLA_FLEET_API_URL') ?: 'https://fleet-api.prd.eu.vn.cloud.tesla.com',
    ),
);
$container->set(
    VehicleService::class,
    static fn(Container $c): VehicleService => new VehicleService(
        $c->get(VehicleStateClient::class),
        $c->get(VehicleRepositoryInterface::class),
        $c->get(TeslaModelRepositoryInterface::class)
    ),
);
$container->set(
    VehicleController::class,
    static fn(Container $c): VehicleController => new VehicleController(
        $c->get(VehicleService::class)
    ),
);
$container->set(
    DashboardController::class,
    static fn(): DashboardController => new DashboardController(Database::pdo()),
);

return $container;
