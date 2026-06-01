<?php
declare(strict_types=1);

/**
 * Classe de limitation de débit (Rate Limiting)
 *
 * Protège l'application contre les abus et attaques par force brute en limitant
 * le nombre de tentatives autorisées pour une action donnée depuis une adresse IP.
 *
 * Le système fonctionne avec une fenêtre de temps glissante : chaque action est
 * comptabilisée et réinitialisée après expiration de la fenêtre.
 *
 * Fonctionnalités principales :
 * - Limitation par IP et par action (login, register, reset-password, etc.)
 * - Fenêtre de temps configurable (par défaut 15 minutes)
 * - Nombre maximal de tentatives configurable (par défaut 5)
 * - Réinitialisation manuelle après succès
 * - Compatible avec les proxies (support de X-Forwarded-For)
 *
 * Le stockage se fait en session pour des raisons de simplicité. Pour une application
 * à fort trafic, il serait préférable d'utiliser Redis ou Memcached.
 *
 * @package MedBoard\Utils
 * @author MedBoard Team
 */
final class RateLimit
{
  /**
   * Vérifie si une action est autorisée pour l'IP courante
   *
   * Implémente un système de fenêtre glissante :
   * 1. Récupère ou initialise le compteur pour l'action + IP
   * 2. Vérifie si la fenêtre de temps est expirée → réinitialise si oui
   * 3. Incrémente le compteur de tentatives
   * 4. Bloque si le nombre maximal de tentatives est dépassé
   *
   * Structure de stockage en session :
   * ```php
   * $_SESSION["ratelimit_{action}_{ip}"] = [
   *     'count' => 3,                    // Nombre de tentatives
   *     'reset_at' => 1735689600         // Timestamp de réinitialisation
   * ]
   * ```
   *
   * Exemple d'usage :
   * ```php
   * // Dans un contrôleur de login
   * if (!RateLimit::check('login', 5, 900)) {
   *     $remaining = RateLimit::getRemainingTime('login');
   *     Flash::set('errors', ["Trop de tentatives. Réessayez dans {$remaining}s"]);
   *     Http::redirect('/auth/login');
   * }
   * ```
   *
   * @param string $action Identifiant de l'action à limiter (login, register, etc.)
   * @param int $maxAttempts Nombre maximum de tentatives autorisées (défaut: 5)
   * @param int $windowSeconds Durée de la fenêtre en secondes (défaut: 900 = 15 min)
   * @return bool True si l'action est autorisée, false si bloquée
   */
  public static function check(string $action, int $maxAttempts = 5, int $windowSeconds = 900): bool
  {
    $ip = self::getClientIp();
    $key = "ratelimit_{$action}_{$ip}";

    // Initialisation du compteur si première tentative
    if (!isset($_SESSION[$key])) {
      $_SESSION[$key] = ['count' => 0, 'reset_at' => time() + $windowSeconds];
    }

    $data = $_SESSION[$key];

    // Réinitialisation si la fenêtre est expirée
    if (time() > $data['reset_at']) {
      $_SESSION[$key] = ['count' => 1, 'reset_at' => time() + $windowSeconds];
      return true;
    }

    // Incrémentation du compteur
    $_SESSION[$key]['count']++;

    // Vérification de la limite
    if ($_SESSION[$key]['count'] > $maxAttempts) {
      return false; // Bloqué
    }

    return true;
  }

  /**
   * Réinitialise le compteur de tentatives pour une action
   *
   * Cette méthode doit être appelée après une action réussie
   * (ex: après une connexion réussie) pour permettre à l'utilisateur
   * de recommencer sans attendre l'expiration de la fenêtre.
   *
   * Exemple d'usage :
   * ```php
   * // Après une connexion réussie
   * if ($loginSuccess) {
   *     RateLimit::reset('login');
   *     // ... suite du traitement
   * }
   * ```
   *
   * @param string $action Identifiant de l'action à réinitialiser
   * @return void
   */
  public static function reset(string $action): void
  {
    $ip = self::getClientIp();
    $key = "ratelimit_{$action}_{$ip}";
    unset($_SESSION[$key]);
  }

  /**
   * Récupère l'adresse IP réelle du client
   *
   * Gère les cas où l'application est derrière un proxy ou un load balancer
   * (comme sur AlwaysData). Vérifie d'abord le header X-Forwarded-For,
   * puis utilise REMOTE_ADDR en fallback.
   *
   * Pour X-Forwarded-For, prend uniquement la première IP de la liste
   * (IP originale du client) pour éviter l'usurpation via injection d'IPs.
   *
   * @return string L'adresse IP du client
   */
  private static function getClientIp(): string
  {
    // AlwaysData et autres proxies utilisent X-Forwarded-For
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
      $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
      return trim($ips[0]); // Première IP = client réel
    }

    // Fallback : connexion directe
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
  }

  /**
   * Calcule le temps restant avant réinitialisation du compteur
   *
   * Retourne le nombre de secondes restantes avant que la fenêtre
   * de limitation n'expire et que le compteur soit réinitialisé.
   *
   * Utile pour afficher un message informatif à l'utilisateur bloqué,
   * par exemple : "Trop de tentatives. Réessayez dans 12 minutes."
   *
   * Exemple d'usage :
   * ```php
   * $remaining = RateLimit::getRemainingTime('login');
   * if ($remaining > 0) {
   *     $minutes = ceil($remaining / 60);
   *     Flash::set('errors', ["Réessayez dans {$minutes} minute(s)"]);
   * }
   * ```
   *
   * @param string $action Identifiant de l'action
   * @return int Nombre de secondes restantes (0 si aucune limitation active)
   */
  public static function getRemainingTime(string $action): int
  {
    $ip = self::getClientIp();
    $key = "ratelimit_{$action}_{$ip}";

    // Aucune limitation en cours
    if (!isset($_SESSION[$key])) {
      return 0;
    }

    // Calcul du temps restant (ne peut pas être négatif)
    return max(0, $_SESSION[$key]['reset_at'] - time());
  }
}
