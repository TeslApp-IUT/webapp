<?php
/**
 * Application routing configuration
 *
 * Structure: 'URL' => [Controller name, Method name, Authentication required (boolean)]
 *
 * - The first element is the name of the controller class to instantiate
 * - The second element is the name of the method to call on this controller
 * - The third element indicates whether the user must be authenticated to access this route
 */
declare(strict_types=1);

use Teslapp\Controllers\Auth\AuthCallbackController;
use Teslapp\Controllers\Auth\AuthController;
use Teslapp\Controllers\Auth\AuthImpersonateController;
use Teslapp\Controllers\Auth\AuthLogoutController;
use Teslapp\Controllers\Auth\AuthSignUpController;
use Teslapp\Controllers\Charging\ChargingController;
use Teslapp\Controllers\Charging\ChargingPlannerController;
use Teslapp\Controllers\Climate\ClimateController;
use Teslapp\Controllers\GeocodingController;
use Teslapp\Controllers\Climate\PreconditioningController;
use Teslapp\Controllers\DashboardController;
use Teslapp\Controllers\StaticPagesController;
use Teslapp\Controllers\VehicleController;
use Teslapp\Controllers\VehicleCommandController;
use Teslapp\Controllers\Auth\ProfileController;

return [
    // URLs for static pages accessible to everyone
    'site/home' => [StaticPagesController::class, 'home', false],
    'home' => [StaticPagesController::class, 'home', false],
    'sitemap' => [StaticPagesController::class, 'sitemap', false],
    'legal' => [StaticPagesController::class, 'legal', false],
    'privacy' => [StaticPagesController::class, 'privacy', false],
    'error/404' => [StaticPagesController::class, 'notFound', false],

    // Dashboard: vehicle picker (no vehicle selected yet)
    'dashboard' => [VehicleController::class, 'select', true],

    // Dashboard pages — vehicle identified by its public_id UUID in the URL
    'dashboard/{vehicleId}/overview' => [DashboardController::class, 'index', true],
    'dashboard/{vehicleId}/vehicle' => [VehicleCommandController::class, 'page', true],
    'dashboard/{vehicleId}/ac' => [ClimateController::class, 'ac', true],
    'dashboard/{vehicleId}/battery' => [ChargingController::class, 'battery', true],

    // Vehicle commands (AJAX JSON endpoints) — vehicleId in URL, resolved to VIN server-side
    'dashboard/{vehicleId}/lock' => [VehicleCommandController::class, 'lock', true],
    'dashboard/{vehicleId}/unlock' => [VehicleCommandController::class, 'unlock', true],
    'dashboard/{vehicleId}/honk' => [VehicleCommandController::class, 'honk', true],
    'dashboard/{vehicleId}/flash' => [VehicleCommandController::class, 'flash', true],
    'dashboard/{vehicleId}/trunk-front' => [VehicleCommandController::class, 'trunkFront', true],
    'dashboard/{vehicleId}/trunk-rear' => [VehicleCommandController::class, 'trunkRear', true],
    'dashboard/{vehicleId}/charge-port-open' => [
        VehicleCommandController::class,
        'chargePortOpen',
        true,
    ],
    'dashboard/{vehicleId}/charge-port-close' => [
        VehicleCommandController::class,
        'chargePortClose',
        true,
    ],
    'dashboard/{vehicleId}/wake' => [VehicleCommandController::class, 'wake', true],

    // Climate preconditioning: schedule CRUD — vehicleId sent as POST field vehicle_id
    'dashboard/{vehicleId}/ac/precondition/create' => [
        PreconditioningController::class,
        'create',
        true,
    ],
    'dashboard/{vehicleId}/ac/precondition/update' => [
        PreconditioningController::class,
        'update',
        true,
    ],
    'dashboard/{vehicleId}/ac/precondition/delete' => [
        PreconditioningController::class,
        'delete',
        true,
    ],
    'dashboard/{vehicleId}/ac/precondition/toggle' => [
        PreconditioningController::class,
        'toggle',
        true,
    ],

    // Charging schedule CRUD — vehicleId sent as POST field vehicle_id
    'dashboard/{vehicleId}/battery/plan/create' => [
        ChargingPlannerController::class,
        'create',
        true,
    ],
    'dashboard/{vehicleId}/battery/plan/update' => [
        ChargingPlannerController::class,
        'update',
        true,
    ],
    'dashboard/{vehicleId}/battery/plan/delete' => [
        ChargingPlannerController::class,
        'delete',
        true,
    ],
    'dashboard/{vehicleId}/battery/plan/toggle' => [
        ChargingPlannerController::class,
        'toggle',
        true,
    ],

    // URLs for authentification
    'auth' => [AuthController::class, 'auth', false],
    'auth/callback' => [AuthCallbackController::class, 'callback', false],
    'auth/signup' => [AuthSignUpController::class, 'signup', false],
    'auth/logout' => [AuthLogoutController::class, 'logout', true],
    'auth/impersonate' => [AuthImpersonateController::class, 'show', true],
    'auth/impersonate/start' => [AuthImpersonateController::class, 'start', true],
    'auth/impersonate/stop' => [AuthImpersonateController::class, 'stop', true],

    // Immediate AC and charging commands (form POST, redirect back)
    'climate/toggle' => [ClimateController::class, 'toggle', true],
    'climate/keeper' => [ClimateController::class, 'setKeeperMode', true],
    'charging/toggle' => [ChargingController::class, 'toggle', true],
    'charging/limit' => [ChargingController::class, 'setLimit', true],
    'charging/amps' => [ChargingController::class, 'setAmps', true],

    // Geocoding
    'geocode' => [GeocodingController::class, 'geocode', true],
    'geocode/reverse' => [GeocodingController::class, 'reverse', true],

    //Profile page
    'profile' => [ProfileController::class, 'profile', false],
];
