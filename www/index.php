<?php
// www/index.php — Front Controller TeslApp
declare(strict_types=1);

use Teslapp\Utils\Csrf;
use Teslapp\Utils\Flash;

/**
 * Chargements : configuration globale, autoload Composer (PSR-4),
 * puis table des routes.
 */
require_once __DIR__ . '/../private/config/config.php';

// Autoload Composer (PSR-4) — requis. Lancer `composer install` si absent.
$autoload = BASE_PATH . '/vendor/autoload.php';
if (!is_file($autoload)) {
    http_response_code(500);
    exit('Autoload Composer manquant. Lancez `composer install`.');
}
require_once $autoload;

$routes = require_once __DIR__ . '/../private/config/routes.php';

// Conteneur DI maison : résout les controllers et leurs dépendances (cf. private/config/container.php)
$container = require_once __DIR__ . '/../private/config/container.php';

session_name('TESLAPP_SESSION');

// Paramètres du cookie de session. SameSite=Lax (et non Strict) pour que le cookie
// soit bien renvoyé au retour du callback OAuth Tesla (navigation top-level cross-site
// depuis auth.tesla.com) ; la protection CSRF reste assurée par le token synchronizer.
$sessionCookieParams = [
    'cookie_secure' => true,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'use_strict_mode' => true,
    'use_only_cookies' => true,
    'cookie_lifetime' => 0,
];
session_start($sessionCookieParams);

// ==================== POLITIQUE D'INACTIVITÉ & ROTATION ====================
$now = time();
$idleTimeout = 1800; // 30 min

if (isset($_SESSION['LAST_ACTIVITY']) && $now - (int) $_SESSION['LAST_ACTIVITY'] >= $idleTimeout) {
    $_SESSION = [];
    session_destroy();
    session_start($sessionCookieParams);
    session_regenerate_id(true);
    Flash::set('info', 'Votre session a expiré.');
}
$_SESSION['LAST_ACTIVITY'] = $now;

if (!isset($_SESSION['CREATED'])) {
    $_SESSION['CREATED'] = $now;
} elseif ($now - (int) $_SESSION['CREATED'] >= 900) {
    // 15 min
    session_regenerate_id(true);
    $_SESSION['CREATED'] = $now;
}

Csrf::ensureToken();

/** ==================== Résolution de la route ====================
 * La route est dérivée du CHEMIN de la requête (front controller — cf. cours MVC §5.5).
 * En prod, le .htaccess réécrit tout vers index.php en préservant REQUEST_URI ; en dev
 * (php -S), les chemins inconnus arrivent aussi à index.php. La query string ?route=
 * reste acceptée en repli explicite. Route par défaut : site/home.
 */
$route = filter_input(INPUT_GET, 'route', FILTER_UNSAFE_RAW);
if (!is_string($route) || $route === '') {
    $route = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
}
$route = trim($route, "/ \t\n\r\0\x0B");
if ($route === '' || $route === 'index.php') {
    $route = 'site/home';
}

if (!isset($routes[$route])) {
    http_response_code(404);

    [$errClass, $errMethod] = $routes['error/404'];
    try {
        $container->get($errClass)->{$errMethod}();
    } catch (Throwable $e) {
        error_log('Échec du rendu de la page 404 : ' . $e->getMessage());
        echo '404 — Page introuvable';
    }
    exit();
}

[$class, $method] = $routes[$route];

/** ==================== Instanciation & exécution ==================== */
try {
    // Vérification du contrôleur (le conteneur est la source de vérité du câblage)
    if (!$container->has($class)) {
        http_response_code(500);
        echo '500 — Contrôleur non enregistré dans le conteneur : ' .
            htmlspecialchars($class, ENT_QUOTES, 'UTF-8');
        exit();
    }

    $controller = $container->get($class);

    if (!is_callable([$controller, $method])) {
        http_response_code(500);
        echo '500 — Méthode introuvable : ' .
            htmlspecialchars($class . '::' . $method, ENT_QUOTES, 'UTF-8');
        exit();
    }

    $controller->{$method}();
} catch (Throwable $e) {
    // Réponse HTTP standard
    http_response_code(500);
    echo '500 — Erreur interne';

    // Journalisation complète
    $logMessage = sprintf(
        "[%s] Exception non interceptée\nType: %s\nMessage: %s\nFichier: %s:%d\nRoute: %s\nClasse: %s\nMéthode: %s\nTrace:\n%s\n",
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
