<?php

declare(strict_types=1);

namespace Teslapp\Models;

use PDO;

/**
 * Shared PostgreSQL connection (PDO), singleton.
 *
 * A single PDO instance is created per request cycle, then reused by
 * all repositories — fixing "one connection per object" antipattern.
 * Settings are from constants defined in private/config/config.php
 * (read from environment, never hard-coded)
 *
 * @package Teslapp\Models
 */
final class Database
{
    /**
     * Shared PDO instance for the entire request cycle.
     *
     * @var PDO|null
     */
    private static ?PDO $instance = null;

    /**
     * Util static class: no instantiation.
     */
    private function __construct() {}

    /**
     * Returns the shared PDO instance, effectively creating it on first call
     *
     * Options: exceptions on SQL errors, associative fetch, native prepared requests
     * (no emulation) and presentation of native int/bool types. DSN includes
     * `sslmode` (DB_SSLMODE) — « require » in production.
     *
     * @return PDO Shared instance connected to the database
     *
     * @throws DatabaseException If connection fails
     */
    public static function pdo(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                'pgsql:host=%s;port=%d;dbname=%s;sslmode=%s',
                DB_HOST,
                (int) DB_PORT,
                DB_NAME,
                DB_SSLMODE,
            );

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_STRINGIFY_FETCHES => false,
                ]);
            } catch (\PDOException $e) {
                // On journalise le détail technique mais on n'expose jamais
                // l'erreur SQL brute à l'utilisateur (cf. erreurs-exceptions.md).
                error_log('DB connection error: ' . $e->getMessage());
                throw new DatabaseException('Error connecting to the database.');
            }
        }

        return self::$instance;
    }

    /**
     * Resets shared instance.
     *
     * Useful to isolate integration tests (cf. bdd-pdo.md §10).
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$instance = null;
    }
}
