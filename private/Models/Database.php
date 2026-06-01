<?php

declare(strict_types=1);

namespace Teslapp\Models;

/**
 * Connexion PostgreSQL partagée (PDO), façon singleton.
 *
 * Une seule instance PDO est créée par cycle de requête puis réutilisée par
 * tous les Repositories — ce qui corrige l'anti-pattern « une connexion par
 * objet ». Les paramètres proviennent des constantes définies dans
 * private/config/config.php (lues depuis l'environnement, jamais en dur).
 *
 * @package Teslapp\Models
 */
final class Database
{
    /**
     * Instance PDO partagée pour tout le cycle de requête.
     *
     * @var \PDO|null
     */
    private static ?\PDO $instance = null;

    /**
     * Classe utilitaire à état statique : pas d'instanciation.
     */
    private function __construct() {}

    /**
     * Retourne l'instance PDO partagée, en la créant à la première demande.
     *
     * Options : exceptions sur erreur SQL, fetch associatif, requêtes préparées
     * natives (pas d'émulation) et préservation des types int/bool natifs. Le DSN
     * inclut `sslmode` (DB_SSLMODE) — « require » en production Feyli.
     *
     * @return \PDO Instance partagée connectée à la base de données
     *
     * @throws \RuntimeException Si la connexion à la base de données échoue
     */
    public static function pdo(): \PDO
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
                self::$instance = new \PDO($dsn, DB_USER, DB_PASS, [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::ATTR_EMULATE_PREPARES => false,
                    \PDO::ATTR_STRINGIFY_FETCHES => false,
                ]);
            } catch (\PDOException $e) {
                // On journalise le détail technique mais on n'expose jamais
                // l'erreur SQL brute à l'utilisateur (cf. erreurs-exceptions.md).
                error_log('DB connection error: ' . $e->getMessage());
                throw new \RuntimeException('Erreur de connexion à la base de données.');
            }
        }

        return self::$instance;
    }

    /**
     * Réinitialise l'instance partagée.
     *
     * Utile pour isoler les tests d'intégration entre eux (cf. bdd-pdo.md §10).
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$instance = null;
    }
}
