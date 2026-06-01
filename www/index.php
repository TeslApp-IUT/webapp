<?php
// medboard/index.php — Front Controller
declare(strict_types=1);

/**
 * Chargements : autoloader (connait déjà "modules\"),
 * config générale (définit notamment MODULES_PATH),
 * et table des routes.
 */
require __DIR__ . '/../private/config/config.php';
require __DIR__ . '/../private/config/autoloader.php';

// L'autoload Composer n'est chargé que s'il est présent : il est introduit avec
// l'outillage Composer. Permet de lancer le socle sans `composer install`.
if (is_file(BASE_PATH . '/vendor/autoload.php')) {
    require BASE_PATH . '/vendor/autoload.php';
}

$routes = require __DIR__ . '/../private/config/routes.php';

session_name('MEDBOARD_SESSION');
session_start([
    'cookie_secure' => true,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
    'use_strict_mode' => true,
    'use_only_cookies' => true,
    'cookie_lifetime' => 0,
]);

// ==================== POLITIQUE D'INACTIVITÉ & ROTATION ====================
$now = time();
$idleTimeout = 1800; // 30 min

if (isset($_SESSION['LAST_ACTIVITY']) && $now - (int) $_SESSION['LAST_ACTIVITY'] >= $idleTimeout) {
    $_SESSION = [];
    session_destroy();
    session_start([
        'cookie_secure' => true,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
        'use_strict_mode' => true,
        'use_only_cookies' => true,
        'cookie_lifetime' => 0,
    ]);
    session_regenerate_id(true);
    $_SESSION['flash']['info'] = 'Votre session a expiré.';
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
 * L'.htaccess doit réécrire /site/home -> index.php?route=site/home
 * Route par défaut : site/home
 */
$route = filter_input(INPUT_GET, 'route', FILTER_UNSAFE_RAW);
$route = $route ? trim($route, "/ \t\n\r\0\x0B") : 'site/home';

if (!isset($routes[$route])) {
    http_response_code(404);

    [$errClass, $errMethod] = $routes['error/404'];
    try {
        (new $errClass())->{$errMethod}();
        exit();
    } catch (Exception $e) {
        error_log($e->getMessage());
    }
}

[$class, $method] = $routes[$route];

/** ==================== Instanciation & exécution ==================== */
try {
    // Vérification du contrôleur
    if (!class_exists($class)) {
        http_response_code(500);
        echo '500 — Classe contrôleur introuvable : ' .
            htmlspecialchars($class, ENT_QUOTES, 'UTF-8');
        exit();
    }

    $controller = new $class();

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
