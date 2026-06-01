<?php
declare(strict_types=1);

/**
 * Modèle de gestion des tokens "Remember Me" (Se souvenir de moi)
 *
 * Cette classe gère les tokens persistants permettant aux utilisateurs
 * de rester connectés entre les sessions. Elle implémente le pattern
 * Selector/Validator pour une sécurité optimale (protection contre
 * les attaques timing et les vols de tokens).
 *
 * Architecture du token :
 * - Selector : Identifiant public du token (non sensible)
 * - Validator : Secret hashé stocké en base, jamais exposé en clair
 * - Expires_at : Date d'expiration pour limiter la durée de validité
 * - User_agent_hash : Empreinte du navigateur pour détecter les vols
 *
 * @package MedBoard\Models
 * @author  MedBoard Team
 * @version 1.0.0
 */
final class RememberTokenModel
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
     * Crée un nouveau token "Remember Me"
     *
     * Insère un nouveau token de persistance dans la base de données.
     * Le token utilise le pattern Selector/Validator pour sécuriser
     * l'authentification automatique. Retourne l'ID généré pour le token.
     *
     * @param array $data Tableau associatif contenant les données du token :
     *                    - 'user_id' (int) : ID de l'utilisateur propriétaire
     *                    - 'selector' (string) : Identifiant public du token (16+ caractères aléatoires)
     *                    - 'validator_hash' (string) : Hash du validateur secret (SHA-256 ou mieux)
     *                    - 'expires_at' (string) : Date d'expiration au format 'Y-m-d H:i:s'
     *                    - 'user_agent_hash' (string) : Hash de l'User-Agent pour détection de vol
     *                    - 'created_at' (string) : Date de création au format 'Y-m-d H:i:s'
     *
     * @return int ID du token créé (remember_token_id)
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO remember_tokens (
                    user_id,
                    selector,
                    validator_hash,
                    expires_at,
                    user_agent_hash,
                    created_at
                ) VALUES (
                    :user_id,
                    :selector,
                    :validator_hash,
                    :expires_at,
                    :user_agent_hash,
                    :created_at
                )
                RETURNING remember_token_id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':user_id'         => $data['user_id'],
            ':selector'        => $data['selector'],
            ':validator_hash'  => $data['validator_hash'],
            ':expires_at'      => $data['expires_at'],
            ':user_agent_hash' => $data['user_agent_hash'],
            ':created_at'      => $data['created_at'],
        ]);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Supprime tous les tokens "Remember Me" associés à un utilisateur
     *
     * Cette méthode est appelée lors de la déconnexion complète ou lors
     * d'un changement de mot de passe pour invalider toutes les sessions
     * persistantes sur tous les appareils de l'utilisateur.
     *
     * @param int $userId ID de l'utilisateur dont il faut supprimer les tokens
     *
     * @return void
     */
    public function deleteForUser(int $userId): void
    {
        $sql = "DELETE FROM remember_tokens WHERE user_id = :user_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
    }

    /**
     * Supprime un token spécifique par son selector
     *
     * Utilisé lors de la déconnexion sur un appareil spécifique, permettant
     * à l'utilisateur de rester connecté sur ses autres appareils. Le selector
     * identifie de manière unique le token à supprimer.
     *
     * @param string $selector Identifiant public du token à supprimer
     *
     * @return void
     */
    public function deleteBySelector(string $selector): void
    {
        $sql = "DELETE FROM remember_tokens WHERE selector = :selector";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':selector' => $selector]);
    }

    /**
     * Recherche un token valide et non expiré par son selector
     *
     * Récupère les informations complètes d'un token "Remember Me" si celui-ci
     * existe et n'est pas expiré. Cette méthode est utilisée lors de la
     * vérification d'une authentification automatique. Le validator_hash
     * retourné sera comparé avec le validator fourni par le client.
     *
     * @param string $selector Identifiant public du token à rechercher
     *
     * @return array|null Tableau associatif contenant toutes les colonnes du token :
     *                    ['remember_token_id', 'user_id', 'selector', 'validator_hash',
     *                     'expires_at', 'user_agent_hash', 'created_at']
     *                    ou null si le token n'existe pas ou est expiré
     */
    public function findValidBySelector(string $selector): ?array
    {
        $sql = "SELECT remember_token_id,
                       user_id,
                       selector,
                       validator_hash,
                       expires_at,
                       user_agent_hash,
                       created_at
                  FROM remember_tokens
                 WHERE selector = :selector
                   AND expires_at > NOW()
                 LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':selector' => $selector]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Supprime tous les tokens expirés de la base de données
     *
     * Méthode de maintenance périodique qui nettoie les tokens "Remember Me"
     * expirés pour éviter l'accumulation de données inutiles. Cette méthode
     * devrait être appelée régulièrement via un cron job ou un task scheduler.
     *
     * @return void
     */
    public function deleteExpired(): void
    {
        $sql = "DELETE FROM remember_tokens WHERE expires_at <= NOW()";
        $this->pdo->exec($sql);
    }
}
