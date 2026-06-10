<?php
/**
 * DI container: explicit build recipes for the application services.
 * Loaded by www/index.php after Composer autoloading.
 */
declare(strict_types=1);

use Teslapp\Controllers\Auth\AuthCallbackController;
use Teslapp\Controllers\Auth\AuthImpersonateController;
use Teslapp\Controllers\Auth\AuthLogoutController;
use Teslapp\Controllers\Auth\AuthSignUpController;
use Teslapp\Controllers\Auth\AuthController;
use Teslapp\Controllers\Charging\ChargingController;
use Teslapp\Controllers\Charging\ChargingPlannerController;
use Teslapp\Controllers\Climate\PreconditioningController;
use Teslapp\Controllers\GeocodingController;
use Teslapp\Controllers\Navigation\NavigationController;
use Teslapp\Models\Charging\ChargingPlannerRepository;
use Teslapp\Models\Charging\ChargingPlannerRepositoryInterface;
use Teslapp\Models\Charging\ChargingPlannerService;
use Teslapp\Models\Charging\ChargingService;
use Teslapp\Models\Auth\AuthRepository;
use Teslapp\Models\Auth\ImpersonationRepository;
use Teslapp\Models\Auth\RememberTokenRepository;
use Teslapp\Models\Climate\PreconditioningPlannerRepository;
use Teslapp\Models\Climate\PreconditioningPlannerRepositoryInterface;
use Teslapp\Models\Climate\PreconditioningService;
use Teslapp\Models\Navigation\NavigationRepository;
use Teslapp\Models\Navigation\NavigationRepositoryInterface;
use Teslapp\Models\Shared\TokenCipher;
use Teslapp\Controllers\StaticPagesController;
use Teslapp\Controllers\DashboardController;
use Teslapp\Controllers\VehicleCommandController;
use Teslapp\Controllers\VehicleController;
use Teslapp\Models\Database;
use Teslapp\Models\Shared\Geocoding\CachingGeocoder;
use Teslapp\Models\Shared\Geocoding\GeocoderInterface;
use Teslapp\Models\Shared\Geocoding\NominatimGeocoder;
use Teslapp\Models\Shared\TeslaApi\ChargingCommandClient;
use Teslapp\Models\Shared\TeslaApi\ClimateCommandClient;
use Teslapp\Models\Shared\TeslaApi\TeslaChargingClient;
use Teslapp\Models\Shared\TeslaApi\TeslaClimateClient;
use Teslapp\Models\Shared\TeslaApi\TeslaCommandClient;
use Teslapp\Models\Shared\TeslaApi\TeslaStateClient;
use Teslapp\Models\Shared\TeslaApi\VehicleCommandClient;
use Teslapp\Models\Shared\TeslaApi\VehicleStateClient;
use Teslapp\Models\Shared\TeslaApi\VehicleTelemetryRepositoryInterface;
use Teslapp\Models\Shared\VehicleTelemetryRepository;
use Teslapp\Models\Vehicle\VehicleCommandService;
use Teslapp\Models\Vehicle\VehicleRepository;
use Teslapp\Models\Vehicle\VehicleRepositoryInterface;
use Teslapp\Models\Vehicle\VehicleService;
use Teslapp\Utils\Container;
use Teslapp\Controllers\Climate\ClimateController;
use Teslapp\Models\Climate\ClimateService;
use Teslapp\Models\Shared\TeslaApi\ClimateClient;
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
    VehicleStateClient::class,
    static fn(): VehicleStateClient => new TeslaStateClient(),
);
$container->set(
    VehicleService::class,
    static fn(Container $c): VehicleService => new VehicleService(
        $c->get(VehicleStateClient::class),
        $c->get(VehicleRepositoryInterface::class),
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
    static fn(Container $c): AuthCallbackController => new AuthCallbackController(
        $c->get(AuthRepository::class),
        $c->get(RememberToken::class),
    ),
);
$container->set(
    AuthSignUpController::class,
    static fn(Container $c): AuthSignUpController => new AuthSignUpController(
        $c->get(AuthRepository::class),
        $c->get(RememberToken::class),
    ),
);

$container->set(
    AuthLogoutController::class,
    static fn(Container $c): AuthLogoutController => new AuthLogoutController(
        $c->get(RememberToken::class),
    ),
);

$container->set(
    ImpersonationRepository::class,
    static fn(): ImpersonationRepository => new ImpersonationRepository(Database::pdo()),
);

$container->set(
    AuthRepository::class,
    static fn(): AuthRepository => new AuthRepository(Database::pdo()),
);

$container->set(
    TokenCipher::class,
    static fn(): TokenCipher => new TokenCipher(
        base64_decode(getenv('TESLAPP_TOKEN_ENCRYPTION_KEY'), strict: true),
    ),
);

$container->set(
    AuthImpersonateController::class,
    static fn(Container $c): AuthImpersonateController => new AuthImpersonateController(
        $c->get(ImpersonationRepository::class),
        $c->get(AuthRepository::class),
        $c->get(TokenCipher::class),
    ),
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
$container->set(
    ClimateClient::class,
    static fn(): ClimateClient => new ClimateClient(TESLA_COMMANDS_DRY_RUN),
);
$container->set(
    ClimateService::class,
    static fn(Container $c): ClimateService => new ClimateService(
        $c->get(ClimateClient::class),
        $c->get(VehicleRepositoryInterface::class),
    ),
);
$container->set(
    ClimateController::class,
    static fn(Container $c): ClimateController => new ClimateController(
        $c->get(ClimateService::class),
        $c->get(PreconditioningService::class),
        $c->get(VehicleRepositoryInterface::class),
        $c->get(VehicleTelemetryRepositoryInterface::class),
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
        $c->get(VehicleRepositoryInterface::class),
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
    PreconditioningService::class,
    static fn(Container $c): PreconditioningService => new PreconditioningService(
        $c->get(PreconditioningPlannerRepositoryInterface::class),
        $c->get(VehicleRepositoryInterface::class),
        $c->get(ClimateCommandClient::class),
    ),
);
$container->set(
    GeocodingController::class,
    static fn(Container $c): GeocodingController => new GeocodingController(
        $c->get(GeocoderInterface::class),
    ),
);
$container->set(
    PreconditioningController::class,
    static fn(Container $c): PreconditioningController => new PreconditioningController(
        $c->get(PreconditioningService::class),
        $c->get(VehicleRepositoryInterface::class),
    ),
);

// Charging (issue #30): command adapter, repository, services, controllers.
$container->set(
    ChargingCommandClient::class,
    static fn(): ChargingCommandClient => new TeslaChargingClient(TESLA_COMMANDS_DRY_RUN),
);
$container->set(
    ChargingPlannerRepositoryInterface::class,
    static fn(): ChargingPlannerRepositoryInterface => new ChargingPlannerRepository(
        Database::pdo(),
    ),
);
$container->set(
    ChargingService::class,
    static fn(Container $c): ChargingService => new ChargingService(
        $c->get(ChargingCommandClient::class),
        $c->get(VehicleRepositoryInterface::class),
    ),
);
$container->set(
    ChargingPlannerService::class,
    static fn(Container $c): ChargingPlannerService => new ChargingPlannerService(
        $c->get(ChargingPlannerRepositoryInterface::class),
        $c->get(VehicleRepositoryInterface::class),
        $c->get(ChargingCommandClient::class),
    ),
);
$container->set(
    ChargingController::class,
    static fn(Container $c): ChargingController => new ChargingController(
        $c->get(ChargingService::class),
        $c->get(ChargingPlannerService::class),
        $c->get(VehicleTelemetryRepositoryInterface::class),
        $c->get(VehicleRepositoryInterface::class),
    ),
);
$container->set(
    ChargingPlannerController::class,
    static fn(Container $c): ChargingPlannerController => new ChargingPlannerController(
        $c->get(ChargingPlannerService::class),
        $c->get(VehicleRepositoryInterface::class),
    ),
);

$container->set(
    NavigationRepositoryInterface::class,
    static fn(Container $c): NavigationRepositoryInterface => new NavigationRepository(
        Database::pdo(),
    ),
);

// Navigation
$container->set(
    NavigationController::class,
    static fn(Container $c): NavigationController => new NavigationController(
        $c->get(VehicleTelemetryRepositoryInterface::class),
        $c->get(VehicleRepositoryInterface::class),
        $c->get(NavigationRepositoryInterface::class),
        new CachingGeocoder($c->get(GeocoderInterface::class), Database::pdo()),
    ),
);

return $container;
