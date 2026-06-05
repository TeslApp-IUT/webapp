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

use Teslapp\Controllers\DashboardController;
use Teslapp\Controllers\CallbackAuthController;
use Teslapp\Controllers\StaticPagesController;
use Teslapp\Controllers\VehicleController;
use Teslapp\Controllers\AuthController;

return [
    // URLs for static pages accessible to everyone
    'site/home' => [StaticPagesController::class, 'home', false],
    'site/sitemap' => [StaticPagesController::class, 'sitemap', false],
    'site/legal' => [StaticPagesController::class, 'legal', false],
    'site/privacy' => [StaticPagesController::class, 'privacy', false],
    'error/404' => [StaticPagesController::class, 'notFound', false],

    // URLs for the post authentification
    'vehicle/select' => [VehicleController::class, 'select', true],
    'vehicle/choose' => [VehicleController::class, 'choose', true],
    'dashboard/overview' => [DashboardController::class, 'index', true],

    // URLs for authentification
    'auth' => [AuthController::class, 'auth', false],
    'callback_auth' => [CallbackAuthController::class, 'callback', false],
];
