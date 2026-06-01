<?php
declare(strict_types=1);

/**
 * Classe de gestion de la connexion à la base de données PostgreSQL
 *
 * Cette classe implémente le pattern Singleton pour la connexion PDO.
 * Elle gère la connexion sécurisée à PostgreSQL avec gestion d'erreurs
 * et options PDO configurables.
 *
 * @package MedBoard\Models
 * @author  MedBoard Team
 * @version 1.0.0
 */
final class Database
{
    /**
     * Instance PDO pour la connexion à la base de données
     *
     * @var PDO|null
     */
    private $pdo = null;

    /**
     * Constructeur - Initialise la connexion à la base de données PostgreSQL
     *
     * Crée une nouvelle connexion PDO avec les paramètres fournis ou ceux
     * définis dans les constantes globales. Configure automatiquement les
     * options de sécurité PDO (mode exception, fetch associatif, prepared statements).
     *
     * @param string|null $host    Hôte de la base de données (défaut: DB_HOST)
     * @param int|null    $port    Port de connexion (défaut: DB_PORT)
     * @param string|null $dbName  Nom de la base de données (défaut: DB_NAME)
     * @param string|null $user    Nom d'utilisateur (défaut: DB_USER)
     * @param string|null $pass    Mot de passe (défaut: DB_PASS)
     * @param string|null $charset Charset de la connexion (défaut: DB_CHARSET)
     * @param array       $options Options PDO supplémentaires
     *
     * @throws RuntimeException Si la connexion à la base de données échoue
     */
    public function __construct(
        ?string $host = null,
        ?int $port = null,
        ?string $dbName = null,
        ?string $user = null,
        ?string $pass = null,
        ?string $charset = null,
        array $options = [],
    ) {
        // Utilisation des valeurs par défaut si non fournies
        $host = $host ?? DB_HOST;
        $port = $port ?? DB_PORT;
        $dbName = $dbName ?? DB_NAME;
        $user = $user ?? DB_USER;
        $pass = $pass ?? DB_PASS;
        $charset = $charset ?? DB_CHARSET;

        // Configuration des options PDO sécurisées par défaut
        // ERRMODE_EXCEPTION : Lance des exceptions en cas d'erreur SQL
        // FETCH_ASSOC : Retourne les résultats sous forme de tableaux associatifs
        // EMULATE_PREPARES : Désactive l'émulation pour utiliser les vrais prepared statements
        $defaultOptions = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $options = $options + $defaultOptions;

        // Construction du DSN (Data Source Name) pour PostgreSQL
        $dsn = sprintf('pgsql:host=%s;port=%d;dbname=%s', $host, $port, $dbName);

        try {
            // Tentative de connexion à la base de données
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            // Log de l'erreur pour le débogage (sans exposer les détails sensibles)
            error_log('DB connection error: ' . $e->getMessage());
            // Lancement d'une exception générique pour l'utilisateur
            throw new RuntimeException('Erreur de connexion à la base de données.');
        }
    }

    /**
     * Retourne l'instance PDO active
     *
     * Méthode d'accès à l'objet PDO pour exécuter des requêtes.
     * Vérifie que la connexion est bien initialisée avant de la retourner.
     *
     * @return PDO Instance PDO connectée à la base de données
     *
     * @throws RuntimeException Si l'instance PDO n'est pas initialisée
     */
    public function getPdo(): PDO
    {
        if (!$this->pdo instanceof PDO) {
            throw new RuntimeException('PDO non initialisé.');
        }
        return $this->pdo;
    }
}
