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
use Teslapp\Controllers\Climate\ClimateController;
use Teslapp\Controllers\GeocodingController;
use Teslapp\Controllers\Climate\PreconditioningController;
use Teslapp\Controllers\DashboardController;
use Teslapp\Controllers\StaticPagesController;
use Teslapp\Controllers\VehicleController;
use Teslapp\Controllers\VehicleCommandController;

return [
    // URLs for static pages accessible to everyone
    'site/home' => [StaticPagesController::class, 'home', false],
    'home' => [StaticPagesController::class, 'home', false],
    'sitemap' => [StaticPagesController::class, 'sitemap', false],
    'legal' => [StaticPagesController::class, 'legal', false],
    'privacy' => [StaticPagesController::class, 'privacy', false],
    'error/404' => [StaticPagesController::class, 'notFound', false],

    // URLs for the post authentification
    'vehicle/select' => [VehicleController::class, 'select', true],
    'vehicle/choose' => [VehicleController::class, 'choose', true],
    'dashboard/overview' => [DashboardController::class, 'index', true],
    'dashboard/vehicle' => [VehicleCommandController::class, 'page', true],

    // Vehicle commands (issue #26) — POST AJAX endpoints answering JSON
    'vehicle/lock' => [VehicleCommandController::class, 'lock', true],
    'vehicle/unlock' => [VehicleCommandController::class, 'unlock', true],
    'vehicle/honk' => [VehicleCommandController::class, 'honk', true],
    'vehicle/flash' => [VehicleCommandController::class, 'flash', true],
    'vehicle/trunk-front' => [VehicleCommandController::class, 'trunkFront', true],
    'vehicle/trunk-rear' => [VehicleCommandController::class, 'trunkRear', true],
    'vehicle/charge-port-open' => [VehicleCommandController::class, 'chargePortOpen', true],
    'vehicle/charge-port-close' => [VehicleCommandController::class, 'chargePortClose', true],
    'vehicle/wake' => [VehicleCommandController::class, 'wake', true],

    // URLs for authentification
    'auth' => [AuthController::class, 'auth', false],
    'auth/callback' => [AuthCallbackController::class, 'callback', false],
    'auth/signup' => [AuthSignUpController::class, 'signup', false],
    'auth/logout' => [AuthLogoutController::class, 'logout', true],
    'auth/impersonate' => [AuthImpersonateController::class, 'show', true],
    'auth/impersonate/start' => [AuthImpersonateController::class, 'start', true],
    'auth/impersonate/stop' => [AuthImpersonateController::class, 'stop', true],

    // URLs for air conditioning
    'dashboard/ac' => [ClimateController::class, 'ac', true],
    'climate/toggle' => [ClimateController::class, 'toggle', true],
    'climate/keeper' => [ClimateController::class, 'setKeeperMode', true],

    // Climate preconditioning: schedule CRUD and address geocoding
    'dashboard/ac/precondition/create' => [PreconditioningController::class, 'create', true],
    'dashboard/ac/precondition/update' => [PreconditioningController::class, 'update', true],
    'dashboard/ac/precondition/delete' => [PreconditioningController::class, 'delete', true],
    'dashboard/ac/precondition/toggle' => [PreconditioningController::class, 'toggle', true],
    'geocode' => [GeocodingController::class, 'geocode', true],
    'geocode/reverse' => [GeocodingController::class, 'reverse', true],
];
