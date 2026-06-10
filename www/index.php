<?php
// www/index.php — TeslApp Front Controller
declare(strict_types=1);

use Teslapp\Models\Auth\ImpersonationRepository;
use Teslapp\Utils\Csrf;
use Teslapp\Utils\Flash;
use Teslapp\Utils\RememberToken;
use Teslapp\Utils\Route;

/**
 * Loading: global configuration, Composer autoload (PSR-4),
 * followed by the routes table.
 */
require_once __DIR__ . '/../private/config/config.php';

// Composer autoload (PSR-4) — required. Run `composer install` if missing.
$autoload = BASE_PATH . '/vendor/autoload.php';
if (!is_file($autoload)) {
    http_response_code(500);
    exit('Autoload Composer manquant. Lancez `composer install`.');
}
require_once $autoload;

$routes = require_once __DIR__ . '/../private/config/routes.php';

// Custom DI container: resolves controllers and their dependencies (see private/config/container.php)
$container = require_once __DIR__ . '/../private/config/container.php';

// Enable strict mode to reduce attack surface
ini_set('session.use_strict_mode', '1');

session_name('TESLAPP_SESSION');

// Session cookie settings. SameSite=Lax (not Strict) so that the cookie
// is properly sent back when the Tesla OAuth callback returns (top-level cross-site navigation
// from auth.tesla.com); CSRF protection is still ensured by the synchronizer token.
$sessionCookieParams = [
    'cookie_secure' => true,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'use_only_cookies' => true,
    'cookie_lifetime' => 0,
];
session_start($sessionCookieParams);

// ==================== INACTIVITY & ROTATION POLICY ====================
$now = time();
$idleTimeout = 1800; // 30 min

if (isset($_SESSION['LAST_ACTIVITY']) && $now - (int) $_SESSION['LAST_ACTIVITY'] >= $idleTimeout) {
    $_SESSION = [];
    session_destroy();
    session_start($sessionCookieParams);
    session_regenerate_id(true);
}

// ==================== REMEMBER-ME FALLBACK ====================
// Only fires when no active session exists (e.g. after idle expiry or browser restart).
// On success, the token is rotated and the session is re-populated.
if (!isset($_SESSION['user_id'])) {
    /** @var RememberToken $rememberToken */
    $rememberToken = $container->get(RememberToken::class);
    $rememberedId = $rememberToken->tryReAuth();
    if ($rememberedId !== null) {
        $_SESSION['user_id'] = $rememberedId;
        session_regenerate_id(true);
    }
}

// Populate the display name for the header if not already set (covers remember-me re-auth).
if (isset($_SESSION['user_id']) && !isset($_SESSION['user_display_name'])) {
    /** @var \Teslapp\Models\Auth\AuthRepository $authRepo */
    $authRepo = $container->get(\Teslapp\Models\Auth\AuthRepository::class);
    $userName = $authRepo->getUserName((string) $_SESSION['user_id']);
    $_SESSION['user_display_name'] = $userName !== null
        ? trim($userName['first_name'] . ' ' . $userName['last_name'])
        : '';
}

$_SESSION['LAST_ACTIVITY'] = $now;

// Load the developer flag once per session (keyed on the real user, not an impersonated one).
if (isset($_SESSION['user_id']) && !array_key_exists('is_developer', $_SESSION)) {
    $realId = (string) ($_SESSION['real_user_id'] ?? $_SESSION['user_id']);
    /** @var ImpersonationRepository $impRepo */
    $impRepo = $container->get(ImpersonationRepository::class);
    $_SESSION['is_developer'] = $impRepo->isDeveloper($realId);
}

if (!isset($_SESSION['CREATED'])) {
    $_SESSION['CREATED'] = $now;
} elseif ($now - (int) $_SESSION['CREATED'] >= 900) {
    // 15 min
    session_regenerate_id(true);
    $_SESSION['CREATED'] = $now;
}

Csrf::ensureToken();

/** ==================== Route Resolution ====================
 * The route is derived from the request's PATH (front controller — see MVC course §5.5).
 * In production, the .htaccess file rewrites everything to index.php while preserving REQUEST_URI; in development
 * (php -S), unknown paths also go to index.php. The query string ?route=
 * remains accepted as an explicit fallback. Default route: site/home.
 */
$route = filter_input(INPUT_GET, 'route', FILTER_UNSAFE_RAW);
if (!is_string($route) || $route === '') {
    $route = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
}
$route = trim($route, "/ \t\n\r\0\x0B");
if ($route === '' || $route === 'index.php') {
    $route = 'site/home';
}

$handler = $routes[$route] ?? null;

if ($handler === null) {
    // Parameterized route matching: patterns like 'dashboard/{vehicleId}/overview'
    $routeParams = [];
    foreach ($routes as $pattern => $candidate) {
        if (!str_contains($pattern, '{')) {
            continue;
        }
        $regex = '#^' . preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $pattern) . '$#';
        if (preg_match($regex, $route, $matches) === 1) {
            $handler = $candidate;
            $routeParams = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            break;
        }
    }
}

if ($handler === null) {
    http_response_code(404);

    [$errClass, $errMethod] = $routes['error/404'];
    try {
        $container->get($errClass)->{$errMethod}();
    } catch (Throwable $e) {
        error_log('Page 404 rendering failed : ' . $e->getMessage());
        echo '404 — Page Not Found';
    }
    exit();
}

Route::setParams($routeParams ?? []);

[$class, $method] = $handler;
$requiresAuth = $handler[2] ?? false;

// Centralised authentication guard: routes flagged requiresAuth need a logged-in user.
// AJAX callers (Accept: application/json — e.g. the command endpoints) get a 401 JSON;
// page navigations are redirected to the login screen.
if ($requiresAuth && empty($_SESSION['user_id'])) {
    // Detect AJAX/JSON callers (the command endpoints) so they get a 401 JSON rather than an
    // HTML redirect that a fetch() would silently follow into a fake "success".
    $wantsJson =
        str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') ||
        ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    if ($wantsJson) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Authentication required']);
    } else {
        $redirectUri = $_SERVER['REQUEST_URI'] ?? '/';
        header('Location: /auth?redirectURI=' . urlencode($redirectUri), true, 302);
    }
    exit();
}

/** ==================== Instantiation & Execution ==================== */
try {
    // Controller verification (the container is the single source of truth for the wiring)
    if (!$container->has($class)) {
        http_response_code(500);
        echo '500 — Controller not registered in the container : ' .
            htmlspecialchars($class, ENT_QUOTES, 'UTF-8');
        exit();
    }

    $controller = $container->get($class);

    if (!is_callable([$controller, $method])) {
        http_response_code(500);
        echo '500 — Method not found : ' .
            htmlspecialchars($class . '::' . $method, ENT_QUOTES, 'UTF-8');
        exit();
    }

    $controller->{$method}();
} catch (Throwable $e) {
    // Standard HTTP response
    http_response_code(500);
    echo '500 — Internal error';

    // Full logging
    $logMessage = sprintf(
        "[%s] Unhandled exception\nType: %s\nMessage: %s\nFile: %s:%d\nRoute: %s\nClass: %s\nMethod: %s\nTrace:\n%s\n",
        date('Y-m-d H:i:s'),
        get_class($e),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $route,
        $class ?? '(inconnue)',
        $method ?? '(inconnue)',
        $e->getTraceAsString(),
    );
    error_log($logMessage);
    exit();
}
