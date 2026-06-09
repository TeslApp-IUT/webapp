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
use Teslapp\Controllers\VehicleCommandController;
use Teslapp\Controllers\VehicleController;
use Teslapp\Models\Database;
use Teslapp\Models\Shared\TeslaApi\TeslaCommandClient;
use Teslapp\Models\Shared\TeslaApi\TeslaStateClient;
use Teslapp\Models\Shared\TeslaApi\VehicleCommandClient;
use Teslapp\Models\Shared\TeslaApi\VehicleStateClient;
use Teslapp\Models\Shared\TeslaApi\VehicleTelemetryRepositoryInterface;
use Teslapp\Models\Shared\VehicleTelemetryRepository;
use Teslapp\Models\Vehicle\TeslaModelRepository;
use Teslapp\Models\Vehicle\TeslaModelRepositoryInterface;
use Teslapp\Models\Vehicle\VehicleCommandService;
use Teslapp\Models\Vehicle\VehicleRepository;
use Teslapp\Models\Vehicle\VehicleRepositoryInterface;
use Teslapp\Models\Vehicle\VehicleService;
use Teslapp\Utils\Container;
use Teslapp\Controllers\Climate\ClimateController;
use Teslapp\Models\Climate\ClimateService;
use Teslapp\Models\Shared\TeslaApi\ClimateClient;

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
    VehicleTelemetryRepositoryInterface::class,
    static fn(): VehicleTelemetryRepositoryInterface => new VehicleTelemetryRepository(
        Database::pdo(),
    ),
);
$container->set(
    DashboardController::class,
    static fn(Container $c): DashboardController => new DashboardController(
        $c->get(VehicleTelemetryRepositoryInterface::class),
        $c->get(VehicleRepositoryInterface::class),
    ),
);
$container->set(AuthController::class, static fn(): AuthController => new AuthController());

$container->set(
    AuthCallbackController::class,
    static fn(): AuthCallbackController => new AuthCallbackController(),
);
$container->set(
    AuthSignUpController::class,
    static fn(): AuthSignUpController => new AuthSignUpController(),
);
$container->set(ClimateClient::class, static fn(): ClimateClient => new ClimateClient());
$container->set(
    ClimateService::class,
    static fn(Container $c): ClimateService => new ClimateService($c->get(ClimateClient::class)),
);
$container->set(
    ClimateController::class,
    static fn(Container $c): ClimateController => new ClimateController(
        $c->get(ClimateService::class),
    ),
);

// Vehicle commands (issue #26): command port -> adapter, then service and controller.
$container->set(
    VehicleCommandClient::class,
    static fn(): VehicleCommandClient => new TeslaCommandClient(TESLA_COMMANDS_DRY_RUN),
);
$container->set(
    VehicleCommandService::class,
    static fn(Container $c): VehicleCommandService => new VehicleCommandService(
        $c->get(VehicleCommandClient::class),
        $c->get(VehicleRepositoryInterface::class),
    ),
);
$container->set(
    VehicleCommandController::class,
    static fn(Container $c): VehicleCommandController => new VehicleCommandController(
        $c->get(VehicleCommandService::class),
    ),
);

return $container;
