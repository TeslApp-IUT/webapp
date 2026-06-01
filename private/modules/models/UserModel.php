<?php
declare(strict_types=1);

/**
 * Modèle de gestion des utilisateurs
 *
 * Cette classe gère toutes les opérations CRUD (Create, Read, Update, Delete)
 * relatives aux utilisateurs de l'application MedBoard. Elle inclut la gestion
 * sécurisée des mots de passe avec Argon2id, la validation des identifiants,
 * et la vérification d'unicité des emails et usernames.
 *
 * @package MedBoard\Models
 * @author  MedBoard Team
 * @version 1.0.0
 */
final class UserModel
{
    /**
     * Instance PDO pour les interactions avec la base de données
     *
     * @var PDO
     */
    private PDO $pdo;

    /**
     * Constructeur - Initialise la connexion PDO
     *
     * Récupère automatiquement l'instance PDO via la classe Database.
     */
    public function __construct()
    {
        $this->pdo = (new Database())->getPdo();
    }

    /**
     * Met à jour le mot de passe hashé d'un utilisateur
     *
     * Cette méthode met à jour le hash du mot de passe pour un utilisateur donné
     * et actualise la date de modification (updated_at). Utile lors d'une réinitialisation
     * de mot de passe ou d'un changement volontaire.
     *
     * @param int    $userId       ID de l'utilisateur
     * @param string $passwordHash Hash du nouveau mot de passe (Argon2id recommandé)
     *
     * @return bool True si exactement une ligne a été mise à jour, false sinon
     */
    public function updatePassword(int $userId, string $passwordHash): bool
    {
        $sql = 'UPDATE users
                   SET password_hash = :hash,
                       updated_at = NOW()           -- si tu as cette colonne
                       -- , password_changed_at = NOW()  -- optionnel si tu la gères
                 WHERE user_id = :id';

        $stmt = $this->pdo->prepare($sql);
        $ok   = $stmt->execute([
            ':hash' => $passwordHash,
            ':id'   => $userId,
        ]);

        return $ok && $stmt->rowCount() === 1;
    }

    /**
     * Récupère un utilisateur par son ID
     *
     * Recherche et retourne les informations complètes d'un utilisateur
     * à partir de son identifiant unique.
     *
     * @param int $userId ID de l'utilisateur à rechercher
     *
     * @return array|null Tableau associatif contenant les colonnes :
     *                    ['user_id', 'firstname', 'lastname', 'username',
     *                     'password_hash', 'email', 'specialization_id']
     *                    ou null si l'utilisateur n'existe pas
     */
    public function findById(int $userId): ?array
    {
        $sql = "SELECT user_id, firstname, lastname, username, password_hash, email, specialization_id
                FROM users
                WHERE user_id = :id
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    /**
     * Récupère un utilisateur à partir d'un login (email OU username)
     *
     * Recherche un utilisateur en utilisant soit son email, soit son username.
     * Utile pour l'authentification où l'utilisateur peut se connecter avec l'un ou l'autre.
     *
     * @param string $login Email ou username de l'utilisateur
     *
     * @return array|null Tableau associatif contenant les colonnes :
     *                    ['user_id', 'firstname', 'lastname', 'username',
     *                     'password_hash', 'email', 'specialization_id']
     *                    ou null si aucun utilisateur trouvé
     */
    public function findByLogin(string $login): ?array
    {
        $sql = "SELECT user_id, firstname, lastname, username, password_hash, email, specialization_id
                FROM users
                WHERE email = :login OR username = :login
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':login' => $login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    /**
     * Met à jour le hash du mot de passe si nécessaire (rehashing Argon2id)
     *
     * Vérifie si le hash actuel du mot de passe utilise les paramètres recommandés
     * d'Argon2id. Si ce n'est pas le cas (ancienne version, paramètres obsolètes),
     * génère un nouveau hash avec les paramètres actuels et met à jour la base de données.
     * Cette méthode améliore progressivement la sécurité des mots de passe.
     *
     * @param int    $userId       ID de l'utilisateur
     * @param string $plainPassword Mot de passe en clair (nécessaire pour le rehashing)
     * @param string $currentHash   Hash actuel stocké en base de données
     *
     * @return void
     */
    public function maybeRehashPassword(int $userId, string $plainPassword, string $currentHash): void
    {
        // Configuration des paramètres Argon2id recommandés
        $opts = [
            'memory_cost' => PASSWORD_ARGON2_DEFAULT_MEMORY_COST,
            'time_cost'   => PASSWORD_ARGON2_DEFAULT_TIME_COST,
            'threads'     => PASSWORD_ARGON2_DEFAULT_THREADS,
        ];

        // Vérification si le hash actuel nécessite une mise à jour
        if (!password_needs_rehash($currentHash, PASSWORD_ARGON2ID, $opts)) {
            return; // Le hash est déjà à jour, pas de rehashing nécessaire
        }

        // Génération d'un nouveau hash avec les paramètres actuels
        $newHash = password_hash($plainPassword, PASSWORD_ARGON2ID, $opts);

        // Mise à jour du hash en base de données
        $upd = $this->pdo->prepare("UPDATE users SET password_hash = :h WHERE user_id = :id");
        $upd->execute([':h' => $newHash, ':id' => $userId]);
    }

    /**
     * Vérifie si un username existe déjà dans la base de données
     *
     * Utilisé lors de l'inscription pour garantir l'unicité des usernames.
     *
     * @param string $username Username à vérifier
     *
     * @return bool True si le username est déjà pris, false sinon
     */
    public function isUsernameTaken(string $username): bool
    {
        $sql = 'SELECT 1 FROM users WHERE username = :u LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':u' => $username]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Vérifie si un email existe déjà dans la base de données
     *
     * Utilisé lors de l'inscription pour garantir l'unicité des adresses email.
     *
     * @param string $email Adresse email à vérifier
     *
     * @return bool True si l'email est déjà pris, false sinon
     */
    public function isEmailTaken(string $email): bool
    {
        $sql = 'SELECT 1 FROM users WHERE email = :e LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':e' => $email]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Crée un nouvel utilisateur dans la base de données
     *
     * Insère un nouvel utilisateur avec toutes ses informations et retourne
     * son ID généré automatiquement. Utilise la clause RETURNING de PostgreSQL
     * pour récupérer l'ID en une seule requête.
     *
     * @param array $data Tableau associatif contenant les données de l'utilisateur :
     *                    - 'firstname' (string) : Prénom de l'utilisateur
     *                    - 'lastname' (string) : Nom de famille de l'utilisateur
     *                    - 'username' (string) : Nom d'utilisateur unique
     *                    - 'password_hash' (string) : Hash du mot de passe (Argon2id)
     *                    - 'email' (string) : Adresse email unique
     *                    - 'specialization_id' (int|null) : ID de la spécialisation (optionnel)
     *
     * @return int|false ID du nouvel utilisateur créé, ou false en cas d'échec
     */
    public function createUser(array $data)
    {
        $sql = <<<SQL
            INSERT INTO users (firstname, lastname, username, password_hash, email, specialization_id)
            VALUES (:firstname, :lastname, :username, :password_hash, :email, :specialization_id)
            RETURNING user_id
        SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':firstname'         => $data['firstname'],
            ':lastname'          => $data['lastname'],
            ':username'          => $data['username'],
            ':password_hash'     => $data['password_hash'],
            ':email'             => $data['email'],
            ':specialization_id' => $data['specialization_id'] ?? null,
        ]);

        return $stmt->fetchColumn(); // user_id
    }
}
