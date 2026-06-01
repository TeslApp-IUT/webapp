<?php
declare(strict_types=1);

/**
 * Contrôleur de gestion des sessions utilisateur
 *
 * Ce contrôleur gère l'authentification des utilisateurs, incluant :
 * - La connexion (login) avec support de l'identifiant par email ou username
 * - La déconnexion (logout)
 * - Le mécanisme "Remember Me" pour une connexion persistante
 * - La limitation du taux de tentatives de connexion (rate limiting)
 * - La régénération sécurisée des identifiants de session
 *
 * Mécanisme "Remember Me" :
 * -------------------------
 * Le système utilise un double token (selector + validator) pour sécuriser
 * la reconnexion automatique :
 *
 * 1. À la connexion (si "remember me" coché) :
 *    - Génération de deux tokens aléatoires cryptographiquement sûrs :
 *      * selector (9 bytes → ~12 chars) : identifie le token en BDD (non secret)
 *      * validator (32 bytes → ~43 chars) : secret partagé avec le client
 *    - Le validator est hashé en SHA-256 avant stockage en base de données
 *    - Les deux tokens sont concaténés (selector:validator) et stockés dans un cookie
 *    - Le cookie est configuré avec les flags de sécurité : HttpOnly, Secure, SameSite
 *    - Durée de validité : 30 jours (configurable via REMEMBER_DURATION_SECONDS)
 *
 * 2. À la reconnexion automatique (dans Auth::check ou middleware) :
 *    - Lecture du cookie MEDBOARD_REMEMBER
 *    - Extraction du selector et du validator
 *    - Recherche en BDD du token via le selector
 *    - Vérification que le hash du validator correspond au validator_hash en BDD
 *    - Vérification que le token n'est pas expiré
 *    - Vérification du user agent (optionnel, pour plus de sécurité)
 *    - Si tout est valide : restauration de la session utilisateur
 *
 * 3. À la déconnexion :
 *    - Suppression de tous les tokens "remember me" de l'utilisateur en BDD
 *    - Suppression du cookie côté client
 *
 * Sécurité :
 * ----------
 * - Les validators ne sont JAMAIS stockés en clair en base de données
 * - Les cookies utilisent HttpOnly (protection XSS) et Secure (HTTPS uniquement)
 * - Rate limiting pour prévenir les attaques par force brute
 * - Protection CSRF sur toutes les actions sensibles
 * - Régénération de l'ID de session après connexion réussie
 *
 * @package Controllers
 * @author MedBoard Team
 * @final Cette classe ne peut pas être étendue pour garantir la sécurité
 */
final class SessionController
{
    /**
     * Instance du modèle utilisateur pour les opérations CRUD
     *
     * @var UserModel
     */
    private UserModel $userModel;

    /**
     * Instance du modèle des tokens "remember me" pour gérer la persistance
     *
     * @var RememberTokenModel
     */
    private RememberTokenModel $rememberTokenModel;

    /**
     * Durée de validité du cookie "remember me" en secondes
     *
     * Valeur par défaut : 30 jours (30 * 24 * 60 * 60 = 2 592 000 secondes)
     * Cette durée définit combien de temps un utilisateur peut rester connecté
     * automatiquement sans avoir à se reconnecter.
     *
     * @var int
     */
    private const REMEMBER_DURATION_SECONDS = 30 * 24 * 60 * 60;

    /**
     * Nom du cookie utilisé pour stocker le token "remember me"
     *
     * Ce cookie contient la valeur "selector:validator" qui permet
     * de reconnecter automatiquement l'utilisateur.
     *
     * @var string
     */
    private const REMEMBER_COOKIE_NAME = 'MEDBOARD_REMEMBER';

    /**
     * Constructeur du contrôleur de session
     *
     * Initialise les modèles nécessaires pour la gestion de l'authentification :
     * - UserModel : pour accéder aux données utilisateur
     * - RememberTokenModel : pour gérer les tokens de reconnexion persistante
     */
    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->rememberTokenModel = new RememberTokenModel();
    }

    /**
     * Point d'entrée pour la route de connexion
     *
     * Dispatcher qui redirige vers la méthode appropriée selon la requête HTTP :
     * - GET : affiche le formulaire de connexion (méthode create)
     * - POST : traite la tentative de connexion (méthode store)
     * - Autres méthodes : retourne une erreur 405 Method Not Allowed
     *
     * @return void
     */
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->store();
        } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->create();
        } else {
            http_response_code(405);
            echo 'Méthode non autorisée.';
        }
    }

    /**
     * Point d'entrée pour la route de déconnexion
     *
     * Dispatcher qui traite la déconnexion uniquement via POST pour des raisons de sécurité :
     * - POST : effectue la déconnexion (méthode destroy)
     * - Autres méthodes : retourne une erreur 405 Method Not Allowed
     *
     * La déconnexion est limitée à POST pour éviter les déconnexions accidentelles
     * via des liens GET et pour se protéger contre les attaques CSRF.
     *
     * @return void
     */
    public function logout() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->destroy();
        } else {
            http_response_code(405);
            echo 'Méthode non autorisée.';
        }
    }

    /**
     * Affiche le formulaire de connexion (page login)
     *
     * Cette méthode :
     * 1. Vérifie que l'utilisateur n'est pas déjà connecté (Auth::requireGuest)
     *    - Si déjà connecté, redirige automatiquement vers /dashboard/index
     * 2. Récupère les données flash pour afficher :
     *    - Les anciennes valeurs saisies (old) en cas d'erreur
     *    - Les messages d'erreur (errors)
     *    - Les messages de succès (success)
     * 3. Charge la vue du formulaire de connexion
     *
     * Les données flash sont consommées (supprimées après lecture) pour éviter
     * qu'elles persistent lors d'un rechargement de page.
     *
     * @return void
     */
    public function create(): void {
        // Redirection automatique si l'utilisateur est déjà authentifié
        Auth::requireGuest(); // Si déjà connecté -> /dashboard/index

        // Récupération des messages flash pour affichage dans le formulaire
        [$old, $errors, $success] = array_values(Flash::consumeMany(['old','errors','success']));

        // Chargement de la vue du formulaire de connexion
        require dirname(__DIR__) . '/views/login.php';
    }

    /**
     * Traite la soumission du formulaire de connexion
     *
     * Cette méthode gère l'ensemble du processus d'authentification :
     *
     * 1. Vérification du rate limiting (5 tentatives max en 15 minutes)
     * 2. Validation du token CSRF
     * 3. Validation des données du formulaire (login + password)
     * 4. Recherche de l'utilisateur par login (email OU username)
     * 5. Vérification du mot de passe avec password_verify()
     * 6. Mise à jour du hash si nécessaire (rehashing automatique)
     * 7. Régénération de l'ID de session pour prévenir la fixation de session
     * 8. Création de la session utilisateur en mémoire
     * 9. Si "Remember Me" coché :
     *    - Génération d'un selector (identifiant du token)
     *    - Génération d'un validator (secret)
     *    - Hashage du validator en SHA-256 pour stockage sécurisé
     *    - Enregistrement en base de données avec date d'expiration
     *    - Création d'un cookie contenant "selector:validator"
     * 10. Redirection vers le tableau de bord
     *
     * En cas d'erreur à n'importe quelle étape, l'utilisateur est redirigé
     * vers le formulaire avec un message d'erreur générique pour éviter
     * la divulgation d'informations (énumération d'utilisateurs).
     *
     * Mécanisme "Remember Me" détaillé :
     * ----------------------------------
     * - Sélecteur (selector) : identifie le token en BDD, peut être public
     * - Validateur (validator) : secret, stocké hashé en BDD, transmis en clair au client
     * - Cookie format : "selector:validator" (exemple: "abc123xyz:def456uvw...")
     * - Le cookie est configuré avec :
     *   * expires : timestamp d'expiration (30 jours)
     *   * path : "/" (accessible sur tout le site)
     *   * domain : domaine du site
     *   * secure : true (HTTPS uniquement en production)
     *   * httponly : true (inaccessible via JavaScript, protection XSS)
     *   * samesite : "Strict" (protection CSRF)
     *
     * @return void
     * @throws Throwable Si une erreur inattendue survient lors du processus
     */
    public function store(): void
    {
        // ====================================================================
        // ÉTAPE 1 : Vérification du rate limiting (protection brute-force)
        // ====================================================================
        // Limite à 5 tentatives de connexion par fenêtre de 900 secondes (15 min)
        if (!RateLimit::check('login', maxAttempts: 5, windowSeconds: 900)) {
            $remaining = RateLimit::getRemainingTime('login');
            $minutes = ceil($remaining / 60);
            Flash::set('errors', ["Trop de tentatives de connexion. Réessayez dans {$minutes} minutes."]);
            Http::redirect('/auth/login');
            exit;
        }

        // ====================================================================
        // ÉTAPE 2 : Validation du token CSRF
        // ====================================================================
        // Vérifie que la requête provient bien du formulaire légitime
        Csrf::requireValid('/auth/login');

        // ====================================================================
        // ÉTAPE 3 : Récupération et sanitisation des données du formulaire
        // ====================================================================
        // Récupération de l'identifiant (email OU username)
        $login     = trim((string) ($_POST['login'] ?? null));
        // Récupération du mot de passe (pas de trim pour préserver les espaces intentionnels)
        $password  = (string) ($_POST['password'] ?? null);
        // Vérification si la case "Remember Me" est cochée
        $remember  = isset($_POST['remember']) && ($_POST['remember'] === '1' || $_POST['remember'] === 'on');

        // Validation basique : les deux champs doivent être remplis
        if ($login === '' || $password === '') {
            Flash::set('errors', ['Identifiants requis.']);
            Flash::set('old', ['login' => $login, 'remember' => $remember ? '1' : '0']);
            Http::redirect('/auth/login');
            exit;
        }

        // Normalisation du login en minuscules pour une comparaison insensible à la casse
        $login = mb_strtolower($login);

        try {
            // ================================================================
            // ÉTAPE 4 : Recherche de l'utilisateur en base de données
            // ================================================================
            // La méthode findByLogin recherche l'utilisateur par email OU username
            $user = $this->userModel->findByLogin($login);
            if (!$user) {
                // Message d'erreur volontairement générique pour éviter l'énumération d'utilisateurs
                Flash::set('errors', ['Identifiants invalides.']);
                Flash::set('old', ['login' => $login, 'remember' => $remember ? '1' : '0']);
                Http::redirect('/auth/login');
                exit;
            }

            // ================================================================
            // ÉTAPE 5 : Vérification du mot de passe
            // ================================================================
            // Récupération du hash stocké en base de données
            $hash = (string)($user['password_hash'] ?? '');
            // Vérification du mot de passe avec la fonction native de PHP
            if ($hash === '' || !password_verify($password, $hash)) {
                // Message d'erreur volontairement générique (même message que si user introuvable)
                Flash::set('errors', ['Identifiants invalides.']);
                Flash::set('old', ['login' => $login, 'remember' => $remember ? '1' : '0']);
                Http::redirect('/auth/login');
                exit;
            }

            // ================================================================
            // ÉTAPE 6 : Authentification réussie - Réinitialisation du rate limit
            // ================================================================
            RateLimit::reset('login');

            // ================================================================
            // ÉTAPE 7 : Mise à jour du hash de mot de passe si nécessaire
            // ================================================================
            // Si l'algorithme de hashage a changé, on rehash le mot de passe
            // Cela permet de migrer progressivement vers des algorithmes plus sûrs
            $this->userModel->maybeRehashPassword((int)$user['user_id'], $password, $hash);

            // ================================================================
            // ÉTAPE 8 : Régénération de l'ID de session (sécurité)
            // ================================================================
            // Prévient les attaques par fixation de session
            session_regenerate_id(true);

            // ================================================================
            // ÉTAPE 9 : Récupération du libellé de spécialisation
            // ================================================================
            // Récupération du nom lisible de la spécialisation à partir de l'ID
            $specName = null;
            $specId = isset($user['specialization_id']) ? (int)$user['specialization_id'] : 0;

            if ($specId > 0) {
                try {
                    $specModel = new SpecializationModel();

                    // Vérification de l'existence de la spécialisation
                    if ($specModel->existsById($specId)) {
                        // Récupération de toutes les paires ID => libellé
                        $pairs = $specModel->getPairs();
                        $specName = $pairs[(string)$specId] ?? null;
                        // Mise en forme du nom (première lettre en majuscule)
                        if ($specName !== null) {
                            $specName = mb_convert_case($specName, MB_CASE_TITLE, 'UTF-8');
                        }
                    } else {
                        error_log("Specialization not found for ID: {$specId}");
                    }
                } catch (Throwable $e) {
                    error_log("Erreur lors de la récupération de la spécialisation (ID {$specId}): " . $e->getMessage());
                    $specName = null;
                }
            }

            // ================================================================
            // ÉTAPE 10 : Création de la session utilisateur en mémoire
            // ================================================================
            // Stockage des informations utilisateur dans la session PHP
            $_SESSION['user'] = [
                'user_id'           => (int)$user['user_id'],
                'firstname'         => $user['firstname'] ?? null,
                'lastname'          => $user['lastname'] ?? null,
                'username'          => $user['username'] ?? null,
                'email'             => $user['email'] ?? null,
                'specialization_id' => $specId ?: null,
                'specialization'    => $specName,
                'login_at'          => time(), // Timestamp de connexion pour tracking
            ];

            // ================================================================
            // ÉTAPE 11 : Gestion du mécanisme "Remember Me"
            // ================================================================
            if ($remember) {
                // ============================================================
                // 11.1 : Nettoyage des anciens tokens de l'utilisateur
                // ============================================================
                // Supprime les tokens précédents pour éviter l'accumulation
                // et garantir qu'un seul token "remember me" est actif
                $this->rememberTokenModel->deleteForUser((int)$user['user_id']);

                // ============================================================
                // 11.2 : Génération du selector (identifiant du token)
                // ============================================================
                // 9 bytes aléatoires → ~12 caractères en base64url
                // Le selector sert à identifier le token en BDD (non secret)
                $selector  = self::base64url(random_bytes(9));   // 12 chars environ

                // ============================================================
                // 11.3 : Génération du validator (secret partagé)
                // ============================================================
                // 32 bytes aléatoires → ~43 caractères en base64url
                // Le validator est le secret qui prouve l'identité du client
                $validator = self::base64url(random_bytes(32));  // 43 chars environ

                // ============================================================
                // 11.4 : Hashage du validator pour stockage sécurisé
                // ============================================================
                // On ne stocke JAMAIS le validator en clair en BDD
                // SHA-256 est utilisé car c'est un hash rapide et suffisant ici
                $validatorHash = hash('sha256', $validator);

                // Calcul du timestamp d'expiration (maintenant + 30 jours)
                $expiresAtTs   = time() + self::REMEMBER_DURATION_SECONDS;

                // ============================================================
                // 11.5 : Enregistrement du token en base de données
                // ============================================================
                // Persiste le token avec toutes les métadonnées de sécurité
                $this->rememberTokenModel->create([
                    'user_id'         => (int)$user['user_id'],
                    'selector'        => $selector,
                    'validator_hash'  => $validatorHash, // Hash SHA-256, jamais le validator en clair
                    'expires_at'      => date('Y-m-d H:i:s', $expiresAtTs),
                    'user_agent_hash' => hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? ''), // Sécurité supplémentaire
                    'created_at'      => date('Y-m-d H:i:s'),
                ]);

                // ============================================================
                // 11.6 : Création du cookie avec selector:validator
                // ============================================================
                // Le cookie contient les deux valeurs séparées par ":"
                // Format : "selector:validator" (les deux en clair côté client)
                $cookieValue = $selector . ':' . $validator;

                // Configuration du cookie avec tous les flags de sécurité
                setcookie(
                    self::REMEMBER_COOKIE_NAME,
                    $cookieValue,
                    [
                        'expires'  => $expiresAtTs,           // Date d'expiration (30 jours)
                        'path'     => '/',                     // Accessible sur tout le site
                        'domain'   => $_SERVER['HTTP_HOST'] ?? '', // Domaine du site
                        'secure'   => true,                    // HTTPS uniquement (production)
                        'httponly' => true,                    // Inaccessible via JavaScript (anti-XSS)
                        'samesite' => 'Strict',                // Protection CSRF (utiliser 'Lax' si besoins cross-site)
                    ]
                );
            } else {
                // ============================================================
                // 11.7 : Gestion du cas où "Remember Me" n'est PAS coché
                // ============================================================
                // Si la case n'est pas cochée, on supprime tout cookie existant
                if (!empty($_COOKIE[self::REMEMBER_COOKIE_NAME])) {
                    // Suppression du cookie côté client (expiration dans le passé)
                    setcookie(
                        self::REMEMBER_COOKIE_NAME,
                        '',
                        [
                            'expires'  => time() - 3600, // Expiration dans le passé
                            'path'     => '/',
                            'domain'   => $_SERVER['HTTP_HOST'] ?? '',
                            'secure'   => true,
                            'httponly' => true,
                            'samesite' => 'Strict',
                        ]
                    );
                }
                // Nettoyage également en base de données pour cohérence
                $this->rememberTokenModel->deleteForUser((int)$user['user_id']);
            }

            // ================================================================
            // ÉTAPE 12 : Redirection vers le tableau de bord
            // ================================================================
            Http::redirect('/dashboard/index');
            exit;

        } catch (Throwable $e) {
            // ================================================================
            // Gestion des erreurs inattendues
            // ================================================================
            // Log de l'erreur pour investigation sans exposer les détails à l'utilisateur
            error_log($e->getMessage());
            // Message d'erreur générique pour l'utilisateur
            Flash::set('errors', ['Erreur interne. Réessaie plus tard.']);
            Flash::set('old', ['login' => $login, 'remember' => $remember ? '1' : '0']);
            Http::redirect('/auth/login');
            exit;
        }
    }

    /**
     * Déconnecte l'utilisateur et nettoie toutes les données de session
     *
     * Cette méthode effectue les opérations suivantes :
     *
     * 1. Validation du token CSRF pour sécuriser la déconnexion
     * 2. Suppression de tous les tokens "Remember Me" en base de données
     * 3. Suppression du cookie "Remember Me" côté client
     * 4. Destruction de la session PHP via Auth::logout()
     * 5. Affichage d'un message de confirmation
     * 6. Redirection vers la page de connexion
     *
     * Nettoyage "Remember Me" :
     * -------------------------
     * - Tous les tokens de l'utilisateur sont supprimés en BDD
     * - Le cookie est expiré côté client (expires dans le passé)
     * - Cela force l'utilisateur à se reconnecter manuellement
     *
     * Sécurité :
     * ----------
     * - Protection CSRF obligatoire (évite les déconnexions forcées)
     * - Nettoyage complet de toutes les traces de session
     * - Message de confirmation pour feedback utilisateur
     *
     * @return void
     */
    public function destroy(): void
    {
        // Validation du token CSRF pour éviter les déconnexions forcées
        Csrf::requireValid('/auth/logout');

        // ====================================================================
        // Nettoyage du mécanisme "Remember Me"
        // ====================================================================

        // Suppression de tous les tokens "Remember Me" de l'utilisateur en BDD
        if (isset($_SESSION['user']['user_id'])) {
            $this->rememberTokenModel->deleteForUser((int)$_SESSION['user']['user_id']);
        }

        // Suppression du cookie "Remember Me" côté client
        if (!empty($_COOKIE[self::REMEMBER_COOKIE_NAME])) {
            // Expiration du cookie en définissant une date dans le passé
            setcookie(
                self::REMEMBER_COOKIE_NAME,
                '',
                [
                    'expires'  => time() - 3600,           // Expiration 1h dans le passé
                    'path'     => '/',                     // Même path que lors de la création
                    'domain'   => $_SERVER['HTTP_HOST'] ?? '', // Même domaine
                    'secure'   => true,                    // HTTPS uniquement
                    'httponly' => true,                    // Inaccessible via JavaScript
                    'samesite' => 'Strict',                // Protection CSRF
                ]
            );
        }

        // Destruction de la session PHP (appel à session_destroy() + nettoyage $_SESSION)
        Auth::logout();

        // Message de confirmation pour l'utilisateur
        Flash::set('success', 'Vous êtes maintenant déconnecté.');

        // Redirection vers la page de connexion
        Http::redirect('/auth/login');
        exit;
    }

    // ========================================================================
    // MÉTHODES UTILITAIRES (HELPERS)
    // ========================================================================

    /**
     * Encode une chaîne binaire en base64 URL-safe
     *
     * Cette méthode convertit des bytes aléatoires en une chaîne compatible URL :
     * - Remplace '+' par '-'
     * - Remplace '/' par '_'
     * - Supprime le padding '=' à la fin
     *
     * Utilisé pour générer les tokens "Remember Me" (selector et validator)
     * qui doivent être transmis dans des cookies et des URLs sans encodage.
     *
     * Exemple :
     * --------
     * random_bytes(9) → base64 standard → "aBc+De/Fg=="
     *                → base64url → "aBc-De_Fg"
     *
     * @param string $bin Chaîne binaire à encoder (typiquement résultat de random_bytes)
     * @return string Chaîne encodée en base64 URL-safe sans padding
     */
    private static function base64url(string $bin): string
    {
        // Encode en base64 standard
        // Remplace les caractères non URL-safe : '+' → '-', '/' → '_'
        // Supprime le padding '=' pour obtenir une chaîne plus compacte
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }
}
