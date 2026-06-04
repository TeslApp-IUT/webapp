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

use Teslapp\Controllers\CallbackAuthController;
use Teslapp\Controllers\StaticPagesController;
use Teslapp\Controllers\AuthController;

return [
    // URLs for static pages accessible to everyone
    'site/home' => [StaticPagesController::class, 'home', false],
    'site/sitemap' => [StaticPagesController::class, 'sitemap', false],
    'site/legal' => [StaticPagesController::class, 'legal', false],
    'site/privacy' => [StaticPagesController::class, 'privacy', false],
    'error/404' => [StaticPagesController::class, 'notFound', false],
    // URLs for authentification
    'auth' => [AuthController::class, 'auth', false],
    'callback_auth' => [CallbackAuthController::class, 'callback', false],
];
