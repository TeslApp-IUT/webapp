<?php

/**
 * Classe de gestion de l'authentification utilisateur
 *
 * Fournit les utilitaires de session : vérification de l'état de connexion,
 * accès aux données de l'utilisateur courant et déconnexion sécurisée.
 *
 * Cette classe utilise le pattern statique pour faciliter l'accès aux méthodes
 * d'authentification dans toute l'application. Elle s'appuie sur le système
 * de sessions PHP.
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
   * Retourne le tableau complet des données utilisateur stockées en session.
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
        'expires' => time() - 42000,
        'path' => $params['path'],
        'domain' => $params['domain'],
        'secure' => $params['secure'],
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
}
