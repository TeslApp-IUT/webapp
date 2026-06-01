<?php

/**
 * Configuration principale de TeslApp.
 *
 * Définit les constantes globales : chemin racine du projet et paramètres
 * de connexion PostgreSQL (lus depuis l'environnement, jamais en dur).
 */

declare(strict_types=1);

/**
 * BASE_PATH : chemin absolu vers la racine du projet (contient private/ et www/).
 */
define('BASE_PATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR);

/**
 * Paramètres de connexion PostgreSQL, lus depuis l'environnement (.env).
 *
 * Note : getenv() retourne `false` (et non `null`) si la variable est absente,
 * donc on utilise `?:` pour retomber sur une chaîne vide (`??` ne capterait pas le `false`).
 */
define('DB_HOST', getenv('DB_HOST') ?: '');
define('DB_PORT', getenv('DB_PORT') ?: '5432');
define('DB_USER', getenv('DB_USER') ?: '');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: '');

/**
 * Mode SSL de la connexion PostgreSQL (libpq) : « prefer » en dev local,
 * « require » imposé en prod Feyli via .env (cf. bdd-pdo.md §2, securite-php.md §1).
 */
define('DB_SSLMODE', getenv('DB_SSLMODE') ?: 'prefer');
