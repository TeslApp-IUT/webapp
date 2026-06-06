<?php
/**
 * DI container: explicit build recipes for the application services.
 * Loaded by www/index.php after Composer autoloading.
 */
declare(strict_types=1);

use Teslapp\Controllers\AuthController;
use Teslapp\Controllers\CallbackAuthController;
use Teslapp\Controllers\StaticPagesController;
use Teslapp\Controllers\VehicleCommandController;
use Teslapp\Models\Database;
use Teslapp\Models\Shared\TeslaApi\TeslaCommandClient;
use Teslapp\Models\Shared\TeslaApi\TeslaStateClient;
use Teslapp\Models\Shared\TeslaApi\VehicleCommandClient;
use Teslapp\Models\Shared\TeslaApi\VehicleStateClient;
use Teslapp\Models\Vehicle\TeslaModelRepository;
use Teslapp\Models\Vehicle\TeslaModelRepositoryInterface;
use Teslapp\Models\Vehicle\VehicleCommandService;
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

$container->set(AuthController::class, static fn(): AuthController => new AuthController());

$container->set(
    CallbackAuthController::class,
    static fn(): CallbackAuthController => new CallbackAuthController(),
);

// Vehicle commands (issue #26): command port -> adapter, then service and controller.
$container->set(
    VehicleCommandClient::class,
    static fn(): VehicleCommandClient => new TeslaCommandClient(),
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
