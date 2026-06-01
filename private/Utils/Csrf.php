<?php

declare(strict_types=1);

namespace Teslapp\Utils;

/**
 * Classe de gestion des tokens CSRF (Cross-Site Request Forgery)
 *
 * Protège l'application contre les attaques CSRF en générant et validant
 * des tokens uniques pour chaque session utilisateur. Ces tokens doivent être
 * inclus dans tous les formulaires sensibles et vérifiés côté serveur.
 *
 * Le token est stocké en session et comparé avec celui soumis via POST
 * en utilisant hash_equals() pour se prémunir contre les attaques par timing.
 *
 * @package Teslapp\Utils
 * @author Teslapp Team
 */
final class Csrf
{
    /**
     * Génère et stocke un token CSRF en session s'il n'existe pas déjà
     *
     * Cette méthode doit être appelée au début de chaque requête nécessitant
     * une protection CSRF (généralement dans l'autoloader ou le routeur).
     *
     * Le token est généré avec random_bytes(32) puis converti en hexadécimal,
     * produisant une chaîne de 64 caractères cryptographiquement sécurisée.
     *
     * Si la session n'est pas démarrée, cette méthode la démarre automatiquement.
     *
     * @return void
     */
    public static function ensureToken(): void
    {
        // Démarrage de la session si nécessaire
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Génération du token s'il n'existe pas
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    /**
     * Vérifie la validité du token CSRF soumis via POST
     *
     * Compare le token stocké en session avec celui reçu dans $_POST.
     * Utilise hash_equals() pour une comparaison timing-safe qui empêche
     * les attaques par analyse temporelle.
     *
     * @param string $key Nom du champ POST contenant le token (défaut: 'csrf_token')
     * @return bool True si le token est valide, false sinon
     */
    public static function checkFromPost(string $key = 'csrf_token'): bool
    {
        $sess = $_SESSION['csrf_token'] ?? '';
        $post = $_POST[$key] ?? '';

        // Vérification que les deux tokens existent et correspondent
        return $sess && $post && hash_equals($sess, $post);
    }

    /**
     * Vérifie le token CSRF et redirige en cas d'échec
     *
     * Méthode utilitaire qui combine la vérification du token CSRF avec
     * une gestion automatique des erreurs et de la redirection.
     *
     * En cas d'échec de validation :
     * - Un message d'erreur est ajouté aux messages flash
     * - Les anciennes données du formulaire peuvent être conservées (si $keepOld = true)
     * - L'utilisateur est redirigé vers l'URL spécifiée
     * - L'exécution du script est arrêtée
     *
     * Cette méthode évite la duplication du code de vérification CSRF
     * dans les différents contrôleurs.
     *
     * @param string $redirectUrl URL de redirection en cas d'échec de validation
     * @param bool $keepOld Si true, conserve les données POST dans les messages flash (défaut: false)
     * @return void Redirige et arrête l'exécution si le token est invalide
     */
    public static function requireValid(string $redirectUrl, bool $keepOld = false): void
    {
        if (!self::checkFromPost()) {
            // Message d'erreur utilisateur
            Flash::set('errors', ['Le formulaire a expiré. Merci de réessayer.']);

            // Conservation des données saisies si demandé
            if ($keepOld) {
                Flash::set('old', $_POST);
            }

            // Redirection et arrêt de l'exécution
            Http::redirect($redirectUrl);
            exit();
        }
    }
}
