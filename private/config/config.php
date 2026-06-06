<?php

/**
 * Main configuration for TeslApp.
 *
 * Defines global constants: project root path and
 * PostgreSQL connection settings (read from the environment, never hardcoded).
 */

declare(strict_types=1);

/**
 * BASE_PATH: absolute path to the project root (contains private/ and www/).
 */
define('BASE_PATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR);

/**
 * Load variables from the project .env into the environment when present.
 * In Docker, env vars are already injected via docker-compose (env_file: .env),
 * so this is a fallback that mainly helps local runs (php -S, CLI scripts).
 * Existing environment values always take precedence (never overwritten).
 */
$envFile = BASE_PATH . '.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $envLine) {
        $envLine = ltrim($envLine);

        // Skip blank lines and comments (# or ;). Values are never inline-commented.
        if (
            $envLine === '' ||
            $envLine[0] === '#' ||
            $envLine[0] === ';' ||
            !str_contains($envLine, '=')
        ) {
            continue;
        }

        [$envKey, $envValue] = explode('=', $envLine, 2);
        $envKey = trim($envKey);
        $envValue = trim($envValue);

        // Strip a single pair of surrounding quotes, if present.
        // Double-quoted values additionally unescape \" → " to match Docker Compose behaviour.
        if (
            strlen($envValue) >= 2 &&
            ($envValue[0] === '"' || $envValue[0] === "'") &&
            $envValue[strlen($envValue) - 1] === $envValue[0]
        ) {
            $quote = $envValue[0];
            $envValue = substr($envValue, 1, -1);
            if ($quote === '"') {
                $envValue = str_replace('\\"', '"', $envValue);
            }
        }

        // Existing environment values always win (never overwritten).
        if ($envKey !== '' && getenv($envKey) === false) {
            putenv($envKey . '=' . $envValue);
            $_ENV[$envKey] = $envValue;
        }
    }
}

/**
 * PostgreSQL connection parameters, read from the environment (.env).
 *
 * Note: getenv() returns `false` (not `null`) if the variable is missing,
 * so we use `?:` to fall back to an empty string (`??` would not handle the `false`).
 */
define('DB_HOST', getenv('DB_HOST') ?: '');
define('DB_PORT', getenv('DB_PORT') ?: '5432');
define('DB_USER', getenv('DB_USER') ?: '');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: '');

/**
 * SSL mode for the PostgreSQL connection (libpq): "prefer" in local development,
 * "require" enforced in Feyli production via .env (see bdd-pdo.md §2, securite-php.md §1).
 */
define('DB_SSLMODE', getenv('DB_SSLMODE') ?: 'prefer');

/**
 * Tesla API mode: 'simulated' (dev/CI — reads the documented fixtures) or 'real'
 * (live Fleet API). Defaults to 'simulated' so the app runs out of the box.
 */
define('TESLA_API_MODE', getenv('TESLA_API_MODE') ?: 'simulated');

/**
 * Filesystem path to the Tesla fixtures (documented API responses) consumed by
 * SimulatedTeslaApiClient.
 */
define('TESLA_FIXTURES_PATH', getenv('TESLA_FIXTURES_PATH') ?: BASE_PATH . 'tests/fixtures/tesla');

/**
 * Development user id, used while there is no real login. Must match the user
 * seeded by db/script_insertion_dev.sql.
 */
define('DEV_USER_ID', getenv('DEV_USER_ID') ?: '00000000-0000-0000-0000-000000000001');
