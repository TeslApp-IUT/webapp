<?php

/**
 * Classe utilitaire pour les opérations HTTP
 *
 * Fournit des méthodes simplifiées pour gérer les redirections HTTP
 * et d'autres opérations liées au protocole HTTP.
 *
 * Cette classe utilise le pattern statique pour un accès facile
 * depuis n'importe quel point de l'application.
 *
 * @package MedBoard\Utils
 * @author MedBoard Team
 */
final class Http
{
  /**
   * Effectue une redirection HTTP et arrête l'exécution
   *
   * Envoie un header HTTP "Location" pour rediriger le navigateur vers
   * une nouvelle URL et termine immédiatement l'exécution du script.
   *
   * La méthode utilise le code de statut 302 (Found) qui indique une
   * redirection temporaire. Ce type de redirection est approprié pour
   * la plupart des cas d'usage de l'application (après soumission de
   * formulaire, connexion, déconnexion, etc.).
   *
   * Le type de retour "never" indique que cette méthode ne retourne jamais
   * (elle termine toujours par exit), ce qui aide l'analyse statique du code.
   *
   * Exemple d'usage :
   * ```php
   * // Redirection simple
   * Http::redirect('/auth/login');
   *
   * // Redirection après traitement
   * if ($success) {
   *     Flash::set('success', 'Inscription réussie');
   *     Http::redirect('/dashboard/index');
   * }
   * ```
   *
   * @param string $url URL de destination (relative ou absolue)
   * @return never Cette méthode ne retourne jamais (exit)
   */
  public static function redirect(string $url): never
  {
    header('Location: ' . $url, true, 302);
    exit();
  }
}
