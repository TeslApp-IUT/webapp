<?php
declare(strict_types=1);

/**
 * Contrôleur de gestion de la réinitialisation des mots de passe
 *
 * Ce contrôleur implémente un processus sécurisé de réinitialisation de mot de passe en deux étapes :
 *
 * 1. Demande de réinitialisation (forgotPassword) :
 *    - L'utilisateur saisit son email
 *    - Un token unique et temporaire est généré
 *    - Un email contenant un lien de réinitialisation est envoyé
 *    - Le système ne révèle jamais si l'email existe ou non (anti-énumération)
 *
 * 2. Réinitialisation effective (resetPassword) :
 *    - L'utilisateur clique sur le lien reçu par email
 *    - Le token est vérifié (validité, expiration, utilisation unique)
 *    - L'utilisateur définit un nouveau mot de passe
 *    - Le mot de passe est hashé avec Argon2id et stocké
 *    - Le token est invalidé pour empêcher toute réutilisation
 *
 * Sécurité :
 * - Protection CSRF sur toutes les soumissions de formulaire
 * - Rate limiting pour éviter les abus (10 tentatives/15min pour forgot, 3 tentatives/1h pour reset)
 * - Tokens URL-safe générés cryptographiquement (32 bytes)
 * - Tokens stockés sous forme de hash SHA-256 en base de données
 * - Expiration des tokens après 30 minutes
 * - Invalidation de tous les anciens tokens lors d'une nouvelle demande
 * - Hachage des mots de passe avec Argon2id
 * - Réponse uniforme pour éviter l'énumération des comptes
 *
 * @package MedBoard\Controllers
 * @author MedBoard Team
 * @version 1.0.0
 */
final class PasswordsController
{
    /**
     * Modèle pour accéder aux données des utilisateurs
     *
     * @var UserModel Instance du modèle UserModel pour rechercher et mettre à jour les utilisateurs
     */
    private UserModel $userModel ;

    /**
     * Modèle pour gérer les demandes de réinitialisation de mot de passe
     *
     * @var PasswordResetModel Instance du modèle PasswordResetModel pour créer, valider et invalider les tokens
     */
    private PasswordResetModel $passwordResetModel ;

    /**
     * Constructeur du contrôleur
     *
     * Initialise les modèles nécessaires pour la gestion des utilisateurs
     * et des demandes de réinitialisation de mot de passe.
     */
    public function __construct()
    {
        $this->userModel  = new UserModel();
        $this->passwordResetModel = new PasswordResetModel();
    }

    /**
     * Point d'entrée pour la demande de réinitialisation de mot de passe
     *
     * Cette méthode orchestre le processus de demande de réinitialisation :
     * - GET : Affiche le formulaire de demande (via create())
     * - POST : Traite la demande et envoie l'email (via store())
     * - Autres méthodes HTTP : Retourne une erreur 405
     *
     * Flux complet :
     * 1. L'utilisateur accède au formulaire (GET)
     * 2. L'utilisateur soumet son email (POST)
     * 3. Un token est généré et stocké
     * 4. Un email avec le lien de réinitialisation est envoyé
     * 5. Redirection avec message de confirmation (sans révéler si l'email existe)
     *
     * @return void
     */
    public function forgotPassword() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST')
        {
            $this->store();
        }
        elseif ($_SERVER['REQUEST_METHOD'] === 'GET')
        {
            $this->create();
        }
        http_response_code(405);
        echo 'Méthode non autorisée.';
    }

    /**
     * Point d'entrée pour la réinitialisation effective du mot de passe
     *
     * Cette méthode orchestre le processus de réinitialisation :
     * - GET : Affiche le formulaire de nouveau mot de passe (via edit())
     * - POST : Valide le token et met à jour le mot de passe (via update())
     * - Autres méthodes HTTP : Retourne une erreur 405
     *
     * Flux complet :
     * 1. L'utilisateur clique sur le lien reçu par email (GET avec token et uid)
     * 2. Le formulaire de nouveau mot de passe est affiché
     * 3. L'utilisateur soumet le nouveau mot de passe (POST)
     * 4. Le token est vérifié (validité, expiration, non-utilisation)
     * 5. Le mot de passe est hashé et mis à jour
     * 6. Le token est marqué comme utilisé
     * 7. Redirection vers la page de connexion
     *
     * @return void
     */
    public function resetPassword() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST')
        {
            $this->update();
        }
        elseif ($_SERVER['REQUEST_METHOD'] === 'GET')
        {
            $this->edit();
        }
        else
        http_response_code(405);
        echo 'Méthode non autorisée.';
    }

    /**
     * Affiche le formulaire de demande de réinitialisation de mot de passe (GET)
     *
     * Cette méthode :
     * - Vérifie que l'utilisateur n'est pas connecté (requireGuest)
     * - Récupère les messages flash (anciennes valeurs, erreurs, succès)
     * - Affiche la vue du formulaire de demande
     *
     * @return void
     */
    public function create()
    {
        // Seuls les visiteurs non connectés peuvent demander une réinitialisation
        Auth::requireGuest();

        // Récupération des données flash pour affichage dans la vue
        // old : anciennes valeurs du formulaire en cas d'erreur
        // errors : messages d'erreur de validation
        // success : message de confirmation
        [$old, $errors, $success] = array_values(Flash::consumeMany(['old','errors','success']));

        require dirname(__DIR__) . '/views/forgot-password.php';
    }

    /**
     * Traite la demande de réinitialisation de mot de passe (POST)
     *
     * Processus de sécurisation :
     * 1. Rate limiting : Max 10 tentatives par 15 minutes
     * 2. Vérification CSRF
     * 3. Validation de l'email
     * 4. Recherche de l'utilisateur (sans révéler s'il existe)
     * 5. Génération d'un token cryptographique URL-safe (32 bytes)
     * 6. Stockage du hash SHA-256 du token en DB avec expiration (30 min)
     * 7. Invalidation de tous les anciens tokens de cet utilisateur
     * 8. Construction du lien de réinitialisation avec token et uid
     * 9. Envoi de l'email avec le lien
     * 10. Réponse uniforme (succès apparent même si l'email n'existe pas)
     *
     * Anti-énumération :
     * Le système renvoie toujours le même message de succès, que l'email
     * existe ou non en base de données. Cela empêche un attaquant de
     * déterminer quels emails sont enregistrés.
     *
     * @return void
     */
    public function store()
    {
        // Protection contre les abus : 10 tentatives maximum sur 15 minutes
        if (!RateLimit::check('forgot-password', maxAttempts: 10, windowSeconds: 900)) {
            $remaining = RateLimit::getRemainingTime('forgot-password');
            $minutes = ceil($remaining / 60);
            Flash::set('errors', ["Trop de tentatives de soumission. Réessayez dans {$minutes} minutes."]);
            Http::redirect('/auth/forgot-password');
            exit;
        }

        // Seuls les visiteurs non connectés peuvent demander une réinitialisation
        Auth::requireGuest();

        // Vérification du token CSRF pour prévenir les attaques CSRF
        Csrf::requireValid('/auth/forgot-password', true);

        // 1) Récupération et sanitization des inputs
        $email = Inputs::sanitizeEmail($_POST['email'] ?? '');

        // Conserver l'email pour réaffichage en cas d'erreur
        Flash::set('old', ['email' => $email]);

        // 2) Validation de l'email
        $errors = [];
        if ($msg = Inputs::validateEmail($email)) {
            $errors['email'] = $msg;
        }

        // Si erreurs de validation, rediriger vers le formulaire
        if ($errors) {
            Flash::set('errors', $errors);
            Http::redirect('/auth/forgot-password');
            return;
        }

        // 3) Recherche de l'utilisateur par email
        // Note : Le système ne révèle jamais si l'email existe ou non (anti-énumération)
        $user = $this->userModel->findByLogin($email);

        // 4) Message de succès uniforme pour éviter l'énumération des comptes
        // Ce message est affiché que l'email existe ou non en base de données
        $publicSuccessMsg = 'Si un compte existe pour cette adresse, un e-mail a été envoyé avec un lien de réinitialisation.';

        // Si l'utilisateur n'existe pas, on affiche quand même un message de succès
        // Cela empêche un attaquant de découvrir quels emails sont enregistrés
        if (!$user) {
            Flash::set('success', $publicSuccessMsg);
            Http::redirect('/auth/forgot-password');
            return;
        }

        // L'utilisateur existe : on réinitialise le compteur de rate limiting
        RateLimit::reset('forgot-password');

        // 5) Génération du token de réinitialisation et persistence
        // Génération d'un token cryptographiquement sûr de 32 bytes
        // Encodé en base64 URL-safe (remplace +/ par -_ et supprime le padding =)
        $plainToken = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '='); // URL-safe

        // Stockage du hash SHA-256 du token en DB (jamais le token en clair)
        $tokenHash  = hash('sha256', $plainToken);

        // Le token expire après 30 minutes
        $expiresAt  = (new DateTimeImmutable('+30 minutes'))->format('Y-m-d H:i:s');

        // Invalider tous les anciens tokens de réinitialisation de cet utilisateur
        // Cela empêche l'utilisation de liens précédents
        $this->passwordResetModel->invalidateForUser((int)$user['user_id']);

        // Création de la nouvelle demande de réinitialisation en base de données
        $resetId = $this->passwordResetModel->create([
            'user_id'    => (int)$user['user_id'],
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
            'created_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);

        // Si la création échoue, afficher une erreur
        if (!$resetId) {
            Flash::set('errors', 'Une erreur est survenue. Merci de réessayer.');
            Http::redirect('/auth/forgot-password');
            return;
        }

        // 6) Construction du lien de réinitialisation
        // Détection du protocole (HTTP ou HTTPS)
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uid    = (int)$user['user_id'];

        // Construction de l'URL complète avec le token en clair et l'ID utilisateur
        // Format : https://example.com/auth/reset-password?token=XXX&uid=123
        $resetLink = sprintf(
            '%s://%s/auth/reset-password?token=%s&uid=%d',
            $scheme,
            $host,
            $plainToken,
            $uid
        );

        // 7) Envoi de l'email de réinitialisation via le service d'email
        try {
            $mailer = new MailService();
            $subject = 'Réinitialisation de votre mot de passe';

            // Version HTML de l'email
            $html = <<<HTML
                <p>Bonjour,</p>
                <p>Vous avez demandé la réinitialisation de votre mot de passe.
                Cliquez sur le lien ci-dessous (valable 30 minutes) :</p>
                <p><a href="{$resetLink}">Réinitialiser mon mot de passe</a></p>
                <p>Si vous n'êtes pas à l'origine de cette demande, vous pouvez ignorer cet e-mail.</p>
            HTML;

            // Version texte brut de l'email (pour les clients email sans HTML)
            $text = "Bonjour,\n\n".
                    "Pour réinitialiser votre mot de passe (valable 30 minutes), suivez ce lien :\n".
                    $resetLink."\n\n".
                    "Si vous n'êtes pas à l'origine de cette demande, ignorez cet e-mail.";

            // Envoi de l'email avec versions HTML et texte
            $mailer->send(to: $email, subject: $subject, htmlBody: $html, textBody: $text);
            Flash::set('success', $publicSuccessMsg);
        } catch (Throwable $e) {
            // En cas d'erreur d'envoi d'email, on log l'erreur
            // mais on affiche quand même un message de succès pour l'anti-énumération
            error_log($e->getMessage());
            Flash::set('success', $publicSuccessMsg);
        }

        // 8) Redirection vers le formulaire avec message de confirmation
        Http::redirect('/auth/forgot-password');
        return;
    }

    /**
     * Affiche le formulaire de définition du nouveau mot de passe (GET)
     *
     * Cette méthode :
     * - Vérifie que l'utilisateur n'est pas connecté (requireGuest)
     * - Récupère et valide les paramètres token et uid de l'URL
     * - Affiche les erreurs si le token ou l'uid sont invalides
     * - Affiche la vue du formulaire de nouveau mot de passe
     *
     * Validation préliminaire :
     * Le token et l'uid sont validés côté format (longueur, caractères autorisés)
     * La validation côté métier (expiration, utilisation) se fait dans update()
     *
     * @return void
     */
    public function edit()
    {
        // Seuls les visiteurs non connectés peuvent réinitialiser leur mot de passe
        Auth::requireGuest();

        // Récupération et sanitization des paramètres GET
        // token : le token de réinitialisation URL-safe envoyé par email
        // uid : l'ID de l'utilisateur
        $token = Inputs::sanitizeBase64UrlToken($_GET['token'] ?? '');
        $uid   = Inputs::sanitizeIntId($_GET['uid'] ?? '');

        // Validation du format du token et de l'uid
        $errors = [];
        if ($msg = Inputs::validateBase64UrlToken($token, min: 24, max: 128, label: 'Le lien')) {
            $errors['token'] = $msg;
        }
        if ($msg = Inputs::validateIntId($uid, 'Utilisateur')) {
            $errors['uid'] = $msg;
        }

        // Si des erreurs de format sont détectées, les stocker pour affichage
        if ($errors) {
            Flash::set('errors', $errors);
        }

        // Récupération des données flash pour affichage dans la vue
        [$old, $errors, $success] = array_values(Flash::consumeMany(['old','errors','success']));

        // Affichage de la vue avec les variables $token et $uid disponibles
        require dirname(__DIR__) . '/views/reset-password.php'; // La vue connait $token et $uid
    }

    /**
     * Traite la réinitialisation effective du mot de passe (POST)
     *
     * Processus de sécurisation et validation :
     * 1. Rate limiting : Max 3 tentatives par heure
     * 2. Vérification CSRF
     * 3. Validation du format du token et de l'uid
     * 4. Validation de la force du mot de passe
     * 5. Validation de la confirmation du mot de passe
     * 6. Vérification du token en base de données :
     *    - Le hash correspond
     *    - Le token n'a pas expiré (< 30 minutes)
     *    - Le token n'a pas déjà été utilisé
     * 7. Hachage du nouveau mot de passe avec Argon2id
     * 8. Mise à jour du mot de passe en base de données
     * 9. Invalidation du token (marqué comme utilisé)
     * 10. Redirection vers la page de connexion
     *
     * Sécurité :
     * - Le mot de passe n'est jamais stocké en session flash
     * - Le token est vérifié à la fois côté format et côté métier
     * - Le hachage Argon2id est vérifié avant stockage
     * - Le token devient inutilisable après usage
     *
     * @return void
     */
    public function update()
    {
        // Protection contre les abus : 3 tentatives maximum par heure
        if (!RateLimit::check('reset-password', maxAttempts: 3, windowSeconds: 3600)) {
            $remaining = RateLimit::getRemainingTime('reset-password');
            $minutes = ceil($remaining / 60);
            Flash::set('errors', ["Trop de tentatives de soumission. Réessayez dans {$minutes} minutes."]);
            Http::redirect('/auth/reset-password');
            exit;
        }

        // Seuls les visiteurs non connectés peuvent réinitialiser leur mot de passe
        Auth::requireGuest();

        // Vérification du token CSRF pour prévenir les attaques CSRF
        Csrf::requireValid('/auth/reset-password', true);

        // 1) Récupération et sanitization des données du formulaire
        $token = Inputs::sanitizeBase64UrlToken($_POST['token'] ?? '');
        $uid   = Inputs::sanitizeIntId($_POST['uid'] ?? '');
        $pwd   = (string)($_POST['password'] ?? '');
        $pwd2  = (string)($_POST['password_confirm'] ?? '');

        // Conserver l'uid pour réaffichage en cas d'erreur
        // IMPORTANT : Ne jamais stocker les mots de passe en session flash
        Flash::set('old', ['uid' => $uid]);

        // 2) Validation des inputs
        $errors = [];

        // Validation du format du token (base64 URL-safe, longueur 24-128)
        if ($msg = Inputs::validateBase64UrlToken($token, min: 24, max: 128, label: 'Le lien')) {
            $errors['token'] = $msg;
        }

        // Validation du format de l'uid (entier positif)
        if ($msg = Inputs::validateIntId($uid, 'Utilisateur')) {
            $errors['uid'] = $msg;
        }

        // Validation de la force du mot de passe (longueur, complexité, etc.)
        if ($msgs = Inputs::validatePasswordStrength($pwd)) {
            // Convertir le tableau d'erreurs en une seule chaîne pour affichage
            $errors['password'] = 'Le mot de passe doit contenir : ' . implode(', ', $msgs);
        }

        // Validation de la confirmation du mot de passe
        if ($msg = Inputs::validatePasswordConfirmation($pwd, $pwd2)) {
            $errors['password_confirm'] = $msg;
        }

        // Si erreurs de validation, rediriger vers le formulaire avec le token et l'uid
        if ($errors) {
            Flash::set('errors', $errors);
            Http::redirect('/auth/reset-password?token='.urlencode($token).'&uid='.(int)$uid);
            return;
        }

        // 3) Vérification du token côté métier (base de données)
        // Vérifie que le token :
        // - Correspond au hash stocké en DB (via SHA-256)
        // - N'a pas expiré (< 30 minutes)
        // - N'a pas déjà été utilisé (used_at IS NULL)
        // - Appartient bien à l'utilisateur spécifié par uid
        $resetRow = $this->passwordResetModel->findValidByUidAndToken((int)$uid, $token);

        // Si le token est invalide, expiré ou déjà utilisé
        if (!$resetRow) {
            Flash::set('errors', 'Lien de réinitialisation invalide ou expiré.');
            Http::redirect('/auth/reset-password?token='.urlencode($token).'&uid='.(int)$uid);
            return;
        }

        // Token valide : on réinitialise le compteur de rate limiting
        RateLimit::reset('reset-password');

        // 4) Hachage du nouveau mot de passe avec Argon2id
        // Argon2id est l'algorithme recommandé pour le hachage de mots de passe
        $hash = password_hash($pwd, PASSWORD_ARGON2ID);

        // Vérification que le hachage a réussi et correspond au format PHC Argon2id
        if ($hash === false || ($msg = Inputs::validateArgon2idPHC($hash))) {
            // Erreur critique : le hachage a échoué ou est invalide
            Flash::set('errors', 'Erreur interne lors du hachage du mot de passe.');
            Http::redirect('/auth/reset-password?token='.urlencode($token).'&uid='.(int)$uid);
            return;
        }

        // 5) Mise à jour du mot de passe et invalidation du token
        try {
            // Mise à jour du mot de passe de l'utilisateur en base de données
            $this->userModel->updatePassword((int)$uid, $hash);

            // Marquer le token comme utilisé (set used_at = NOW())
            // Cela empêche toute réutilisation du même lien de réinitialisation
            $this->passwordResetModel->markUsedById((int)$resetRow['user_id']); // set used_at = NOW()

            // Message de succès et redirection vers la page de connexion
            Flash::set('success', 'Votre mot de passe a été réinitialisé. Vous pouvez vous connecter.');
            Http::redirect('/auth/login');
            return;
        } catch (Throwable $e) {
            // En cas d'erreur de base de données, logger l'erreur et afficher un message générique
            Flash::set('errors', 'Erreur interne lors de la mise à jour du mot de passe.');
            Http::redirect('/auth/reset-password?token='.urlencode($token).'&uid='.(int)$uid);
            return;
        }
    }
}
