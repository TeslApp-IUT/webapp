<?php
declare(strict_types=1);

/**
 * Modèle de gestion des demandes de réinitialisation de mot de passe
 *
 * Cette classe gère le cycle de vie complet des tokens de réinitialisation
 * de mot de passe : création, validation, utilisation et invalidation.
 * Elle implémente un système sécurisé basé sur des tokens à usage unique
 * avec expiration temporelle.
 *
 * Workflow typique :
 * 1. Utilisateur demande un reset -> create() génère un token
 * 2. Utilisateur clique sur le lien -> findValidByUidAndToken() valide
 * 3. Utilisateur change son mot de passe -> markUsedById() invalide le token
 * 4. Invalidation des anciens tokens -> invalidateForUser()
 *
 * @package MedBoard\Models
 * @author  MedBoard Team
 * @version 1.0.0
 */
final class PasswordResetModel
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
     * Crée une nouvelle demande de réinitialisation de mot de passe
     *
     * Insère un nouveau token de réinitialisation dans la base de données.
     * Le token est hashé (SHA-256) avant stockage pour plus de sécurité.
     * Retourne l'ID utilisateur si l'insertion réussit.
     *
     * @param array $data Tableau associatif contenant les données de réinitialisation :
     *                    - 'user_id' (int) : ID de l'utilisateur demandant le reset
     *                    - 'token_hash' (string) : Hash SHA-256 du token généré aléatoirement
     *                    - 'expires_at' (string) : Date d'expiration au format 'Y-m-d H:i:s' (recommandé: 1h)
     *                    - 'created_at' (string) : Date de création au format 'Y-m-d H:i:s'
     *
     * @return int|null ID de l'utilisateur si succès, null si échec de l'insertion
     */
    public function create(array $data): ?int
    {
        $sql = 'INSERT INTO password_resets (user_id, token_hash, expires_at, created_at)
                VALUES (:user_id, :token_hash, :expires_at, :created_at)
                RETURNING user_id';
        $stmt = $this->pdo->prepare($sql);
        if (!$stmt->execute([
            ':user_id'    => $data['user_id'],
            ':token_hash' => $data['token_hash'],
            ':expires_at' => $data['expires_at'],
            ':created_at' => $data['created_at'],
        ])) {
            return null;
        }
        return (int)$stmt->fetchColumn();
    }

    /**
     * Invalide tous les tokens actifs d'un utilisateur
     *
     * Marque tous les tokens de réinitialisation non utilisés d'un utilisateur
     * comme utilisés en mettant à jour used_at. Cette méthode est appelée après
     * un changement de mot de passe réussi ou lors d'une nouvelle demande de reset
     * pour empêcher la réutilisation des anciens tokens.
     *
     * @param int $userId ID de l'utilisateur dont il faut invalider les tokens
     *
     * @return void
     */
    public function invalidateForUser(int $userId): void
    {
        // Marque comme utilisés tous les anciens tokens encore actifs
        $sql = 'UPDATE password_resets
                   SET used_at = now()
                 WHERE user_id = :uid AND used_at IS NULL';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':uid' => $userId]);
    }

    /**
     * Recherche un token valide par ID utilisateur et token en clair
     *
     * Vérifie qu'un token de réinitialisation existe, n'est pas expiré,
     * n'a pas été utilisé, et correspond au hash stocké en base de données.
     * Utilisé pour valider un lien de réinitialisation avant de permettre
     * le changement de mot de passe.
     *
     * @param int    $userId     ID de l'utilisateur
     * @param string $plainToken Token en clair fourni dans le lien (sera hashé en SHA-256)
     *
     * @return array|null Tableau associatif contenant toutes les colonnes de password_resets
     *                    ou null si le token est invalide, expiré, ou utilisé
     */
    public function findValidByUidAndToken(int $userId, string $plainToken): ?array
    {
        // Hashage du token fourni pour comparaison avec celui stocké en base
        $hash = hash('sha256', $plainToken);

        // Recherche d'un token valide : non utilisé (used_at IS NULL) et non expiré
        $sql = 'SELECT * FROM password_resets
                 WHERE user_id = :uid
                   AND token_hash = :hash
                   AND used_at IS NULL
                   AND expires_at > now()
                 ORDER BY user_id DESC
                 LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':uid' => $userId, ':hash' => $hash]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Marque un token de réinitialisation comme utilisé
     *
     * Met à jour la colonne used_at avec l'horodatage actuel pour empêcher
     * la réutilisation du token. Cette méthode est appelée immédiatement
     * après qu'un utilisateur a réussi à changer son mot de passe.
     *
     * @param int $id ID utilisateur (user_id) du token à marquer comme utilisé
     *
     * @return void
     */
    public function markUsedById(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE password_resets SET used_at = now() WHERE user_id = :user_id');
        $stmt->execute([':user_id' => $id]);
    }
}
