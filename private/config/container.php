<?php
/**
 * DI container: explicit build recipes for the application services.
 * Loaded by www/index.php after Composer autoloading.
 */
declare(strict_types=1);

use Teslapp\Controllers\Auth\AuthCallbackController;
use Teslapp\Controllers\Auth\AuthController;
use Teslapp\Controllers\Auth\AuthSignUpController;
use Teslapp\Controllers\Climate\GeocodeController;
use Teslapp\Controllers\Climate\PreconditioningController;
use Teslapp\Controllers\DashboardController;
use Teslapp\Controllers\StaticPagesController;
use Teslapp\Controllers\VehicleCommandController;
use Teslapp\Controllers\VehicleController;
use Teslapp\Models\Climate\ClimateService;
use Teslapp\Models\Climate\PreconditioningPlannerRepository;
use Teslapp\Models\Climate\PreconditioningPlannerRepositoryInterface;
use Teslapp\Models\Database;
use Teslapp\Models\Shared\Geocoding\GeocoderInterface;
use Teslapp\Models\Shared\Geocoding\NominatimGeocoder;
use Teslapp\Models\Shared\TeslaApi\ClimateCommandClient;
use Teslapp\Models\Shared\TeslaApi\TeslaClimateClient;
use Teslapp\Models\Shared\TeslaApi\TeslaCommandClient;
use Teslapp\Models\Shared\TeslaApi\TeslaStateClient;
use Teslapp\Models\Shared\TeslaApi\VehicleCommandClient;
use Teslapp\Models\Shared\TeslaApi\VehicleStateClient;
use Teslapp\Models\Vehicle\TeslaModelRepository;
use Teslapp\Models\Vehicle\TeslaModelRepositoryInterface;
use Teslapp\Models\Vehicle\VehicleCommandService;
use Teslapp\Models\Vehicle\VehicleRepository;
use Teslapp\Models\Vehicle\VehicleRepositoryInterface;
use Teslapp\Models\Vehicle\VehicleService;
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
    static fn(): AuthSignUpController => new AuthSignUpController(),
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

// Climate preconditioning: geocoder, command adapter, repository, service, controllers.
$container->set(
    GeocoderInterface::class,
    static fn(): GeocoderInterface => new NominatimGeocoder(
        NOMINATIM_BASE_URL,
        NOMINATIM_USER_AGENT,
    ),
);
$container->set(
    ClimateCommandClient::class,
    static fn(): ClimateCommandClient => new TeslaClimateClient(TESLA_COMMANDS_DRY_RUN),
);
$container->set(
    PreconditioningPlannerRepositoryInterface::class,
    static fn(): PreconditioningPlannerRepositoryInterface => new PreconditioningPlannerRepository(
        Database::pdo(),
    ),
);
$container->set(
    ClimateService::class,
    static fn(Container $c): ClimateService => new ClimateService(
        $c->get(PreconditioningPlannerRepositoryInterface::class),
        $c->get(VehicleRepositoryInterface::class),
        $c->get(ClimateCommandClient::class),
    ),
);
$container->set(
    GeocodeController::class,
    static fn(Container $c): GeocodeController => new GeocodeController(
        $c->get(GeocoderInterface::class),
    ),
);
$container->set(
    PreconditioningController::class,
    static fn(Container $c): PreconditioningController => new PreconditioningController(
        $c->get(ClimateService::class),
    ),
);

return $container;
