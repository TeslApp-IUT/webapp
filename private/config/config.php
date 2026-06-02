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
