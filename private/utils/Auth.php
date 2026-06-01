<?php

/**
 * Classe de gestion de l'authentification utilisateur
 *
 * Gère l'authentification des utilisateurs, la vérification des sessions,
 * le système "Remember Me" avec rotation des tokens, et la déconnexion sécurisée.
 *
 * Cette classe utilise le pattern statique pour faciliter l'accès aux méthodes
 * d'authentification dans toute l'application. Elle s'appuie sur le système
 * de sessions PHP et implémente un mécanisme de "Remember Me" sécurisé avec
 * rotation automatique des tokens.
 *
 * @package MedBoard\Utils
 * @author MedBoard Team
 */
final class Auth
{
    /**
     * Vérifie si un utilisateur est actuellement authentifié
     *
     * @return bool True si un utilisateur est connecté, false sinon
     */
    public static function check(): bool
    {
        return isset($_SESSION['user']);
    }

    /**
     * Récupère les données de l'utilisateur actuellement connecté
     *
     * Retourne le tableau complet des données utilisateur stockées en session
     * (user_id, firstname, lastname, username, email, login_at).
     *
     * @return array|null Les données de l'utilisateur ou null si non connecté
     */
    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    /**
     * Récupère l'identifiant de l'utilisateur connecté
     *
     * @return int|null L'ID de l'utilisateur ou null si non connecté
     */
    public static function id(): ?int
    {
        return $_SESSION['user']['user_id'] ?? null;
    }

    /**
     * Force la redirection vers la page de connexion si l'utilisateur n'est pas authentifié
     *
     * Vérifie si l'utilisateur est connecté et le redirige vers /auth/login si ce n'est pas le cas.
     * Cette méthode doit être appelée au début des contrôleurs nécessitant une authentification.
     *
     * @return void
     */
    public static function requireLogin(): void
    {
        if (!self::check()) {
            Http::redirect('/auth/login');
            exit;
        }
    }

    /**
     * Force la redirection vers le dashboard si l'utilisateur est déjà authentifié
     *
     * Vérifie si l'utilisateur est connecté et le redirige vers /dashboard/index si c'est le cas.
     * Cette méthode doit être appelée au début des pages publiques (login, register, etc.)
     * pour éviter qu'un utilisateur connecté y accède.
     *
     * @return void
     */
    public static function requireGuest(): void {
        if (self::check()) {
            Http::redirect('/dashboard/index');
            exit;
        }
    }

    /**
     * Déconnecte l'utilisateur de manière sécurisée
     *
     * Effectue une déconnexion complète en trois étapes :
     * 1. Purge de toutes les données de session en mémoire
     * 2. Suppression du cookie de session côté client (avec expiration dans le passé)
     * 3. Destruction du fichier de session côté serveur
     *
     * Cette méthode respecte les paramètres de sécurité du cookie (secure, httponly, samesite)
     * pour éviter toute fuite de données lors de la déconnexion.
     *
     * @return void
     */
    public static function logout(): void
    {
        // Purge de la session en mémoire
        $_SESSION = [];

        // Purge du cookie de session
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            // On réécrit le cookie expiré avec les mêmes attributs
            setcookie(session_name(), '', [
                'expires'  => time() - 42000,
                'path'     => $params['path'],
                'domain'   => $params['domain'],
                'secure'   => $params['secure'],
                'httponly' => $params['httponly'],
                // garde le même SameSite si dispo
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }

        // Destruction du stockage serveur
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
    
    /**
     * Tente une connexion automatique à partir du cookie "Remember Me"
     *
     * Implémente un système de "Remember Me" sécurisé avec rotation automatique des tokens.
     * Le processus suit ces étapes :
     *
     * 1. Vérifie qu'aucun utilisateur n'est déjà connecté
     * 2. Récupère et parse le cookie MEDBOARD_REMEMBER (format: selector:validator)
     * 3. Recherche le token en base de données via le selector
     * 4. Vérifie le validator hashé et le user-agent (protection anti-vol de cookie)
     * 5. Charge les données de l'utilisateur
     * 6. ROTATION: supprime l'ancien token et en crée un nouveau (sécurité renforcée)
     * 7. Régénère l'ID de session et hydrate $_SESSION avec les données utilisateur
     *
     * En cas d'échec de validation, le token est immédiatement supprimé pour prévenir
     * les attaques par force brute.
     *
     * @return void
     */
    public static function autoLoginFromRememberCookie(): void
    {
        // Déjà connecté, on ne fait rien
        if (isset($_SESSION['user'])) {
            return;
        }

        // Récupération et validation du format du cookie
        $cookie = $_COOKIE['MEDBOARD_REMEMBER'] ?? '';
        if (!$cookie || strpos($cookie, ':') === false) {
            return;
        }

        // Extraction du selector et du validator
        [$selector, $validator] = explode(':', $cookie, 2);
        if ($selector === '' || $validator === '') {
            return;
        }

        // Recherche du token en base (doit vérifier expires_at > NOW())
        $rememberModel = new RememberTokenModel();
        $row = $rememberModel->findValidBySelector($selector);

        if (!$row) {
            return;
        }

        // Vérifications de sécurité : hash validator + user_agent
        $uaOk = hash_equals($row['user_agent_hash'] ?? '', hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? ''));
        $validatorOk = hash_equals($row['validator_hash'] ?? '', hash('sha256', $validator));

        // En cas d'échec, suppression immédiate du token (anti-bruteforce)
        if (!$validatorOk || !$uaOk) {
            $rememberModel->deleteBySelector($selector);
            return;
        }

        // Chargement des données utilisateur
        $userModel = new UserModel();
        $user = $userModel->findById((int)$row['user_id']);
        if (!$user) {
            $rememberModel->deleteBySelector($selector);
            return;
        }

        // ROTATION DU TOKEN : suppression de l'ancien
        $rememberModel->deleteBySelector($selector);

        // Génération d'un nouveau token avec nouveaux selector/validator
        $newSelector  = self::base64url(random_bytes(9));
        $newValidator = self::base64url(random_bytes(32));
        $rememberModel->create([
            'user_id'         => (int)$user['user_id'],
            'selector'        => $newSelector,
            'validator_hash'  => hash('sha256', $newValidator),
            'expires_at'      => date('Y-m-d H:i:s', time() + 30*24*60*60), // 30 jours
            'user_agent_hash' => hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? ''),
            'created_at'      => date('Y-m-d H:i:s'),
        ]);

        // Mise à jour du cookie avec le nouveau token
        setcookie('MEDBOARD_REMEMBER', $newSelector . ':' . $newValidator, [
            'expires'  => time() + 30*24*60*60,
            'path'     => '/',
            'domain'   => $_SERVER['HTTP_HOST'] ?? '',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);

        // Hydratation de la session comme lors d'un login classique
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'user_id'   => (int)$user['user_id'],
            'firstname' => $user['firstname'] ?? null,
            'lastname'  => $user['lastname'] ?? null,
            'username'  => $user['username'] ?? null,
            'email'     => $user['email'] ?? null,
            'login_at'  => time(),
        ];
    }

    /**
     * Encode une chaîne binaire en Base64 URL-safe
     *
     * Convertit une chaîne binaire en format base64url en remplaçant les caractères
     * spéciaux (+, /) par des caractères URL-safe (-, _) et en supprimant le padding (=).
     * Cette méthode est utilisée pour générer les tokens du système "Remember Me".
     *
     * @param string $bin La chaîne binaire à encoder (généralement issue de random_bytes)
     * @return string La chaîne encodée en base64url
     */
    private static function base64url(string $bin): string
    {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }
}
