<?php

declare(strict_types=1);

namespace Teslapp\Utils;

/**
 * Classe de gestion des messages flash en session
 *
 * Fournit une interface simplifiée pour stocker et récupérer des données
 * temporaires en session, particulièrement utile pour les messages d'erreur,
 * de succès, et la conservation des données de formulaire entre requêtes.
 *
 * Les messages flash sont typiquement utilisés pour :
 * - Afficher des messages de confirmation après une action (verrouillage, connexion Tesla, etc.)
 * - Conserver les erreurs de validation entre requêtes
 * - Préserver les données saisies dans un formulaire après redirection
 *
 * Cette classe utilise $_SESSION comme backend de stockage et propose
 * des méthodes "consume" pour récupérer et supprimer automatiquement
 * les données après lecture (pattern "flash").
 *
 * @package Teslapp\Utils
 */
final class Flash
{
    /**
     * Stocke une valeur en session sous une clé donnée
     *
     * Permet de définir n'importe quelle valeur (string, array, etc.)
     * dans la session pour un usage ultérieur.
     *
     * Exemple d'usage :
     * - Flash::set('errors', ['Email invalide'])
     * - Flash::set('success', 'Véhicule verrouillé')
     * - Flash::set('old', $_POST)
     *
     * @param string $key Clé d'identification de la donnée
     * @param mixed $value Valeur à stocker
     * @return void
     */
    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Récupère une valeur depuis la session sans la supprimer
     *
     * Lit une valeur en session et retourne une valeur par défaut
     * si la clé n'existe pas. La valeur reste en session après lecture.
     *
     * Utilisé quand on veut consulter une valeur sans la consommer.
     *
     * @param string $key Clé d'identification de la donnée
     * @param mixed $default Valeur retournée si la clé n'existe pas (défaut: null)
     * @return mixed La valeur trouvée ou la valeur par défaut
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Récupère une valeur depuis la session et la supprime immédiatement
     *
     * Implémente le pattern "flash message" : lit une valeur une seule fois
     * puis la supprime de la session. Parfait pour les messages d'erreur
     * ou de succès qui ne doivent être affichés qu'une fois.
     *
     * Exemple d'usage typique :
     * ```php
     * // Dans le contrôleur (après validation)
     * Flash::set('errors', ['Email invalide']);
     * Http::redirect('/site/home');
     *
     * // Dans la vue
     * $errors = Flash::consume('errors', []);
     * // La variable $errors contient le tableau, et elle est supprimée de la session
     * ```
     *
     * @param string $key Clé d'identification de la donnée
     * @param mixed $default Valeur retournée si la clé n'existe pas (défaut: null)
     * @return mixed La valeur trouvée ou la valeur par défaut
     */
    public static function consume(string $key, mixed $default = null): mixed
    {
        $val = $_SESSION[$key] ?? $default;
        unset($_SESSION[$key]);
        return $val;
    }

    /**
     * Récupère et supprime plusieurs valeurs en une seule fois
     *
     * Variante de consume() qui permet de récupérer plusieurs clés
     * simultanément et de les supprimer de la session.
     *
     * Retourne un tableau associatif contenant les valeurs récupérées,
     * avec null pour les clés inexistantes.
     *
     * Exemple d'usage :
     * ```php
     * $data = Flash::consumeMany(['errors', 'success', 'old']);
     * // $data = ['errors' => [...], 'success' => null, 'old' => [...]]
     * ```
     *
     * @param array $keys Tableau des clés à récupérer et supprimer
     * @return array Tableau associatif clé => valeur
     */
    public static function consumeMany(array $keys): array
    {
        $out = [];
        foreach ($keys as $k) {
            $out[$k] = self::consume($k);
        }
        return $out;
    }

    /**
     * Ajoute une ou plusieurs valeurs à un tableau existant en session
     *
     * Si la clé n'existe pas, crée un nouveau tableau.
     * Si la clé existe et contient un tableau, fusionne les valeurs.
     * Si la clé existe mais n'est pas un tableau, la convertit en tableau.
     *
     * Utile pour accumuler plusieurs erreurs ou messages successifs.
     *
     * Exemple d'usage :
     * ```php
     * Flash::push('errors', 'Email invalide');
     * Flash::push('errors', 'Mot de passe trop court');
     * // $_SESSION['errors'] = ['Email invalide', 'Mot de passe trop court']
     * ```
     *
     * @param string $key Clé d'identification du tableau
     * @param mixed $value Valeur(s) à ajouter (peut être un tableau ou une valeur simple)
     * @return void
     */
    public static function push(string $key, mixed $value): void
    {
        $cur = $_SESSION[$key] ?? [];
        $_SESSION[$key] = array_merge((array) $cur, (array) $value);
    }
}
