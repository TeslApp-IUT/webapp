<?php
/**
 * Contrôleur de gestion de l'inscription des utilisateurs
 *
 * Ce contrôleur gère l'affichage du formulaire d'inscription et le traitement
 * de la soumission avec validation complète des données et création du compte.
 */
declare(strict_types=1);

/**
 * Class RegistrationController
 *
 * Gère le processus complet d'inscription d'un nouvel utilisateur :
 * - Affichage du formulaire avec liste des spécialisations médicales
 * - Validation des données saisies (nom, email, mot de passe, etc.)
 * - Vérification de l'unicité (username et email)
 * - Hachage sécurisé du mot de passe avec Argon2id
 * - Création du compte en base de données
 * - Protection anti-spam avec rate limiting
 */
final class RegistrationController
{
    /**
     * Instance du modèle utilisateur pour les opérations en base de données
     * @var UserModel
     */
    private UserModel $userModel;

    /**
     * Constructeur du contrôleur
     * Initialise le modèle utilisateur nécessaire aux opérations
     */
    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    /**
     * Méthode de routage principale pour l'inscription
     *
     * Dirige vers la méthode appropriée selon la méthode HTTP :
     * - GET : affiche le formulaire d'inscription
     * - POST : traite la soumission du formulaire
     *
     * @return void
     */
    public function register(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Traitement de la soumission du formulaire
            $this->store();
        } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
            // Affichage du formulaire
            $this->create();
        } else {
            // Méthode HTTP non supportée
            http_response_code(405);
            echo 'Méthode non autorisée.';
        }
    }

    /**
     * Affiche le formulaire d'inscription
     *
     * Cette méthode :
     * - Vérifie que l'utilisateur n'est pas déjà connecté
     * - Récupère les messages flash (erreurs, anciennes valeurs, succès)
     * - Charge la liste des spécialisations médicales depuis la base de données
     * - Affiche le formulaire d'inscription avec toutes les données nécessaires
     *
     * @return void
     */
    public function create(): void
    {
        // Redirection si l'utilisateur est déjà authentifié
        Auth::requireGuest();

        // Initialisation des variables pour PHPStan
        $old = [];
        $errors = [];
        $success = null;
        $specializations = [];

        // Récupération des messages flash (données sauvegardées en session)
        [$old, $errors, $success] = array_values(Flash::consumeMany(['old', 'errors', 'success']));

        // Chargement de la liste des spécialisations médicales
        try {
            require_once __DIR__ . '/../models/SpecializationModel.php';
            $specModel = new SpecializationModel();
            $specializations = $specModel->getPairs();
        } catch (Throwable $e) {
            // Log de l'erreur pour debugging
            error_log('[RegistrationController::create] Failed to fetch specializations: ' . $e->getMessage());
            // Ajout d'un message d'erreur utilisateur
            $errors = array_filter(array_merge((array)$errors, ['Impossible de charger la liste des spécialisations.']));
            $specializations = [];
        }

        // Inclusion de la vue (les variables seront accessibles dans le template)
        require dirname(__DIR__) . '/views/register.php';
    }

    /**
     * Traite la soumission du formulaire d'inscription
     *
     * Cette méthode effectue les opérations suivantes :
     * 1. Vérification du rate limiting (protection anti-spam)
     * 2. Validation du token CSRF
     * 3. Récupération et nettoyage des données du formulaire
     * 4. Validation complète de tous les champs
     * 5. Vérification de l'unicité du username et de l'email
     * 6. Hachage sécurisé du mot de passe
     * 7. Création du compte en base de données
     * 8. Redirection vers la page de connexion en cas de succès
     *
     * En cas d'erreur à n'importe quelle étape, l'utilisateur est redirigé
     * vers le formulaire avec les messages d'erreur appropriés.
     *
     * @return void
     */
    public function store(): void
    {
        /**
         * Protection contre les tentatives répétées (rate limiting)
         * Limite : 10 tentatives par heure
         */
        if (!RateLimit::check('register', maxAttempts: 10, windowSeconds: 3600)) {
            $remaining = RateLimit::getRemainingTime('register');
            $minutes = ceil($remaining / 60);
            Flash::set('errors', ["Trop de tentatives de création de compte. Réessayez dans {$minutes} minutes."]);
            Http::redirect('/auth/register');
            exit;
        }

        // Validation du token CSRF pour éviter les attaques CSRF
        Csrf::requireValid('/auth/register', true);

        /**
         * Récupération et nettoyage des données du formulaire
         * Toutes les données sont sanitizées avant validation
         */
        $firstname = Inputs::sanitizeName($_POST['firstname'] ?? '');
        $lastname = Inputs::sanitizeName($_POST['lastname'] ?? '');
        $username = Inputs::sanitizeUsername($_POST['username'] ?? '');
        $email = Inputs::sanitizeEmail($_POST['email'] ?? '');
        $specializationId = Inputs::sanitizeIntId($_POST['specialization'] ?? '');

        // Les mots de passe ne sont jamais nettoyés, seulement récupérés
        $password = (string)($_POST['password'] ?? '');
        $password2 = (string)($_POST['password_confirm'] ?? '');
        $termsAccepted = isset($_POST['terms']) && $_POST['terms'] === 'on';

        /**
         * Sauvegarde des données pour réaffichage en cas d'erreur
         * Note : les mots de passe ne sont JAMAIS sauvegardés dans les flash
         */
        Flash::set('old', [
            'firstname' => $firstname,
            'lastname' => $lastname,
            'username' => $username,
            'email' => $email,
            'specialization' => $_POST['specialization'] ?? '',
        ]);

        /**
         * Validation de tous les champs du formulaire
         * Chaque champ a ses propres règles de validation
         */
        $errors = [];

        // Validation de l'acceptation des conditions
        if (!$termsAccepted) {
            $errors[] = 'Vous devez accepter les conditions d\'utilisation et la politique de confidentialité.';
        }

        // Validation des champs texte (longueur, caractères autorisés)
        if ($msg = Inputs::validateName($firstname, max: 32, label: 'Le prénom')) $errors[] = $msg;
        if ($msg = Inputs::validateName($lastname, max: 32, label: 'Le nom')) $errors[] = $msg;
        if ($msg = Inputs::validateUsername($username, min: 3, max: 32)) $errors[] = $msg;
        if ($msg = Inputs::validateEmail($email, max: 254)) $errors[] = $msg;

        /**
         * Validation de la spécialisation (optionnelle)
         * Si fournie, doit être un ID valide et exister en base de données
         */
        if ($msg = Inputs::validateIntId($specializationId, 'Spécialisation')) {
            $errors[] = $msg;
        } elseif ($specializationId !== null) {
            try {
                require_once __DIR__ . '/../models/SpecializationModel.php';
                $specModel = new SpecializationModel();
                if (!$specModel->existsById($specializationId)) {
                    $errors[] = 'Spécialisation invalide.';
                }
            } catch (Throwable $e) {
                error_log('[Register] specialization exists check error: ' . $e->getMessage());
                $errors[] = 'Erreur interne sur la spécialisation.';
            }
        }

        /**
         * Validation du mot de passe
         * Vérifie la complexité (longueur, majuscules, chiffres, caractères spéciaux)
         * et la correspondance avec la confirmation
         */
        if ($msgs = Inputs::validatePasswordStrength($password)) {
            foreach ($msgs as $msg) {
                $errors[] = $msg;
            }
        }
        if ($msg = Inputs::validatePasswordConfirmation($password, $password2)) $errors[] = $msg;

        // Si des erreurs de validation, retour au formulaire
        if ($errors) {
            Flash::set('errors', $errors);
            Http::redirect('/auth/register');
            return;
        }

        /**
         * Vérification de l'unicité du username et de l'email
         * Empêche la création de comptes en double
         */
        try {
            $existsErr = [];
            if ($this->userModel->isUsernameTaken($username)) $existsErr[] = 'Ce nom d\'utilisateur est déjà utilisé.';
            if ($this->userModel->isEmailTaken($email)) $existsErr[] = 'Cet email est déjà utilisé.';
            if ($existsErr) {
                Flash::set('errors', $existsErr);
                Http::redirect('/auth/register');
                return;
            }
        } catch (Throwable $e) {
            error_log('[Register] uniqueness check error: ' . $e->getMessage());
            Flash::set('errors', ['Erreur interne lors de la vérification des doublons.']);
            Http::redirect('/auth/register');
            return;
        }

        /**
         * Hachage du mot de passe avec Argon2id
         * Argon2id est l'algorithme recommandé pour le hachage de mot de passe
         */
        $hash = password_hash($password, PASSWORD_ARGON2ID);
        if ($hash === false || ($msg = Inputs::validateArgon2idPHC($hash))) {
            if ($hash === false) error_log('[Register] password_hash returned false');
            Flash::set('errors', [$msg ?: 'Erreur interne lors du hachage du mot de passe.']);
            Http::redirect('/auth/register');
            return;
        }

        // Réinitialisation du compteur de rate limiting après validation réussie
        RateLimit::reset('register');

        /**
         * Création du compte utilisateur en base de données
         * En cas d'erreur (notamment violation de contrainte d'unicité),
         * un message d'erreur approprié est affiché
         */
        try {
            $userId = $this->userModel->createUser([
                'firstname' => $firstname,
                'lastname' => $lastname,
                'username' => $username,
                'password_hash' => $hash,
                'email' => $email,
                'specialization_id' => $specializationId,
            ]);

            // Succès : redirection vers la page de connexion
            Flash::set('success', 'Compte créé avec succès, vous pouvez vous connecter.');
            Http::redirect('/auth/login');
            return;

        } catch (PDOException $e) {
            // Gestion des erreurs de contrainte d'unicité PostgreSQL
            if ($e->getCode() === '23505') {
                $msg = $e->getMessage();
                $human = 'Ce nom d\'utilisateur ou cet email est déjà utilisé.';
                if (stripos($msg, 'username') !== false) $human = 'Ce nom d\'utilisateur est déjà utilisé.';
                if (stripos($msg, 'email') !== false) $human = 'Cet email est déjà utilisé.';
                Flash::set('errors', [$human]);
                Http::redirect('/auth/register');
                return;
            }
            // Autres erreurs de base de données
            error_log('[Register] DB error: ' . $e->getMessage());
            Flash::set('errors', ['Erreur interne lors de la création du compte.']);
            Http::redirect('/auth/register');
            return;

        } catch (Throwable $e) {
            // Erreur inattendue
            error_log('[Register] Fatal: ' . $e->getMessage());
            Flash::set('errors', ['Erreur interne inattendue.']);
            Http::redirect('/auth/register');
            return;
        }
    }
}
