<?php

declare(strict_types=1);

namespace Teslapp\Utils;

/**
 * Classe utilitaire de validation et sanitization des entrées utilisateur
 *
 * Fournit un ensemble complet de méthodes pour valider et nettoyer les données
 * entrantes (formulaires, API, etc.). Chaque type de donnée (nom, email, username,
 * password, etc.) dispose de méthodes de sanitization et de validation dédiées.
 *
 * Les expressions régulières sont centralisées en constantes pour assurer la cohérence
 * avec les contraintes SQL CHECK définies en base de données.
 *
 * Pattern d'utilisation :
 * 1. Sanitize : nettoie et normalise la donnée (trim, lowercase, collapse spaces)
 * 2. Validate : vérifie la conformité et retourne un message d'erreur ou null
 *
 * @package Teslapp\Utils
 * @author Teslapp Team
 */
final class Inputs
{
    /* ===============================
     *  REGEX centrales (alignées avec les contraintes CHECK SQL)
     * =============================== */

    /**
     * Expression régulière pour les noms (firstname, lastname)
     * Autorise : lettres minuscules (avec accents), apostrophes, espaces, tirets
     *
     * @var string
     */
    public const RE_NAME = '/^[a-zà-öø-ÿ\' -]+$/u';

    /**
     * Expression régulière pour les noms d'utilisateur
     * Doit commencer par une lettre minuscule, puis lettres, chiffres, points, tirets, underscores
     *
     * @var string
     */
    public const RE_USER = '/^[a-z][a-z0-9_.-]*$/';

    /**
     * Expression régulière pour les adresses e-mail
     * Format standard : local@domaine.tld
     *
     * @var string
     */
    public const RE_EMAIL = '/^[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$/';

    /**
     * Expression régulière pour valider le format des hashs Argon2id (PHC)
     * Vérifie le format complet du hash password_hash avec ARGON2ID
     *
     * @var string
     */
    public const RE_ARGON2 = '/^\$argon2id\$v=\d+\$m=\d+,t=\d+,p=\d+\$[A-Za-z0-9+\/=]+\$[A-Za-z0-9+\/=]+$/';

    /* ===============================
     *  Helpers génériques
     * =============================== */
    /**
     * Compresse les espaces multiples en un seul espace
     *
     * Remplace toute séquence d'espaces blancs (espaces, tabulations, retours à la ligne)
     * par un simple espace, puis supprime les espaces en début et fin de chaîne.
     *
     * @param string $s La chaîne à traiter
     * @return string La chaîne avec espaces normalisés
     */
    public static function collapseSpaces(string $s): string
    {
        // Remplace toute séquence d'espaces blancs par un seul espace
        $s = preg_replace('/\s+/u', ' ', $s);
        return trim($s);
    }

    /**
     * Nettoie et normalise une chaîne de caractères
     *
     * Méthode générique de sanitization appliquant plusieurs transformations :
     * - trim() : suppression des espaces en début et fin
     * - collapseSpaces() : normalisation des espaces multiples (optionnel)
     * - mb_strtolower() : conversion en minuscules UTF-8 (optionnel)
     *
     * @param string $s La chaîne à nettoyer
     * @param bool $lower Convertir en minuscules (défaut: false)
     * @param bool $collapseSpaces Compresser les espaces multiples (défaut: true)
     * @return string La chaîne nettoyée
     */
    public static function sanitizeString(
        string $s,
        bool $lower = false,
        bool $collapseSpaces = true,
    ): string {
        $s = trim($s);
        if ($collapseSpaces) {
            $s = self::collapseSpaces($s);
        }
        if ($lower) {
            $s = mb_strtolower($s, 'UTF-8');
        }
        return $s;
    }

    /**
     * Valide la longueur d'une chaîne de caractères
     *
     * Vérifie que la longueur de la chaîne (en nombre de caractères UTF-8) respecte
     * les limites min/max définies. Retourne un message d'erreur personnalisé si invalide.
     *
     * @param string $s La chaîne à valider
     * @param int|null $min Longueur minimale requise (null = pas de minimum)
     * @param int|null $max Longueur maximale autorisée (null = pas de maximum)
     * @param string $label Label utilisé dans le message d'erreur
     * @return string|null Message d'erreur ou null si valide
     */
    public static function validateLength(
        string $s,
        ?int $min = null,
        ?int $max = null,
        string $label = 'La valeur',
    ): ?string {
        $len = mb_strlen($s, 'UTF-8');
        if ($min !== null && $len < $min) {
            return "$label doit faire au moins $min caractères.";
        }
        if ($max !== null && $len > $max) {
            return "$label ne doit pas dépasser $max caractères.";
        }
        return null;
    }

    /* ===============================
     *  Name (firstname / lastname)
     * =============================== */

    /**
     * Nettoie un nom (prénom ou nom de famille)
     *
     * Applique : trim, conversion en minuscules, compression des espaces multiples.
     *
     * @param string $s Le nom à nettoyer
     * @return string Le nom nettoyé
     */
    public static function sanitizeName(string $s): string
    {
        // trim + lower + collapse espaces
        return self::sanitizeString($s, lower: true, collapseSpaces: true);
    }

    /**
     * Valide un nom (prénom ou nom de famille)
     *
     * Vérifie que le nom :
     * - N'est pas vide
     * - Ne dépasse pas la longueur maximale
     * - Respecte le format RE_NAME (lettres minuscules avec accents, apostrophes, espaces, tirets)
     *
     * @param string $s Le nom à valider
     * @param int $max Longueur maximale autorisée (défaut: 32)
     * @param string $label Label pour le message d'erreur (défaut: 'Le nom')
     * @return string|null Message d'erreur ou null si valide
     */
    public static function validateName(string $s, int $max = 32, string $label = 'Le nom'): ?string
    {
        if ($s === '') {
            return "$label est requis.";
        }
        if ($msg = self::validateLength($s, min: null, max: $max, label: $label)) {
            return $msg;
        }
        if (!preg_match(self::RE_NAME, $s)) {
            return "$label contient des caractères non autorisés.";
        }
        return null;
    }

    /* ===============================
     *  Username
     * =============================== */

    /**
     * Nettoie un nom d'utilisateur
     *
     * Applique : trim, conversion en minuscules, compression des espaces multiples.
     *
     * @param string $s Le nom d'utilisateur à nettoyer
     * @return string Le nom d'utilisateur nettoyé
     */
    public static function sanitizeUsername(string $s): string
    {
        return self::sanitizeString($s, lower: true, collapseSpaces: true);
    }

    /**
     * Valide un nom d'utilisateur
     *
     * Vérifie que le username :
     * - N'est pas vide
     * - Respecte les longueurs min/max
     * - Commence par une lettre minuscule
     * - Ne contient que lettres, chiffres, points, tirets, underscores (RE_USER)
     *
     * @param string $s Le nom d'utilisateur à valider
     * @param int $min Longueur minimale (défaut: 3)
     * @param int $max Longueur maximale (défaut: 32)
     * @return string|null Message d'erreur ou null si valide
     */
    public static function validateUsername(string $s, int $min = 3, int $max = 32): ?string
    {
        if ($s === '') {
            return "Le nom d'utilisateur est requis.";
        }
        if ($msg = self::validateLength($s, $min, $max, "Le nom d'utilisateur")) {
            return $msg;
        }
        if (!preg_match(self::RE_USER, $s)) {
            return "Le nom d'utilisateur doit commencer par une lettre et ne contenir que lettres, chiffres, points, tirets et underscores.";
        }
        return null;
    }

    /* ===============================
     *  Email
     * =============================== */

    /**
     * Nettoie une adresse e-mail
     *
     * Applique : trim, conversion en minuscules, compression des espaces multiples.
     *
     * @param string $s L'adresse e-mail à nettoyer
     * @return string L'adresse e-mail nettoyée
     */
    public static function sanitizeEmail(string $s): string
    {
        return self::sanitizeString($s, lower: true, collapseSpaces: true);
    }

    /**
     * Valide une adresse e-mail
     *
     * Vérifie que l'email :
     * - N'est pas vide
     * - Ne dépasse pas la longueur maximale (RFC 5321 : 254 caractères)
     * - Respecte le format RE_EMAIL (local@domaine.tld)
     *
     * @param string $s L'adresse e-mail à valider
     * @param int $max Longueur maximale autorisée (défaut: 254)
     * @return string|null Message d'erreur ou null si valide
     */
    public static function validateEmail(string $s, int $max = 254): ?string
    {
        if ($s === '') {
            return "L'email est requis.";
        }
        if ($msg = self::validateLength($s, min: null, max: $max, label: "L'email")) {
            return $msg;
        }
        if (!preg_match(self::RE_EMAIL, $s)) {
            return "Format d'email invalide.";
        }
        return null;
    }

    /* ===============================
     *  Spécialisation (ID entier > 0) — nullable
     * =============================== */

    /**
     * Nettoie et convertit un ID en entier positif
     *
     * Convertit une chaîne en entier si elle représente un nombre positif valide.
     * Retourne null si la chaîne est vide, non numérique, ou si l'ID est <= 0.
     *
     * @param string|null $s La chaîne représentant l'ID
     * @return int|null L'ID en tant qu'entier positif ou null
     */
    public static function sanitizeIntId(?string $s): ?int
    {
        $s = trim((string) $s);
        if ($s === '') {
            return null;
        }
        if (!ctype_digit($s)) {
            return null;
        }
        $i = (int) $s;
        return $i > 0 ? $i : null;
    }

    /**
     * Valide un identifiant entier
     *
     * Vérifie qu'un ID est soit null (si nullable accepté) soit un entier strictement positif.
     *
     * @param int|null $id L'identifiant à valider
     * @param string $label Label pour le message d'erreur (défaut: 'Identifiant')
     * @return string|null Message d'erreur ou null si valide
     */
    public static function validateIntId(?int $id, string $label = 'Identifiant'): ?string
    {
        if ($id === null) {
            return null;
        } // nullable OK
        if ($id <= 0) {
            return "$label invalide.";
        }
        return null;
    }

    /* ===============================
     *  Password (plain + hash PHC)
     * =============================== */

    /**
     * Valide la longueur minimale d'un mot de passe
     *
     * Vérifie que le mot de passe contient au moins le nombre d'octets requis.
     * Note : utilise strlen() pour compter les octets, pas les caractères UTF-8.
     *
     * @param string $plain Le mot de passe en clair
     * @param int $minBytes Nombre minimum d'octets (défaut: 8)
     * @return string|null Message d'erreur ou null si valide
     */
    public static function validatePasswordMinBytes(string $plain, int $minBytes = 8): ?string
    {
        if (strlen($plain) < $minBytes) {
            return "Le mot de passe doit contenir au moins $minBytes caractères.";
        }
        return null;
    }

    /**
     * Valide la robustesse d'un mot de passe selon des critères de sécurité
     *
     * Vérifie que le mot de passe respecte les critères de sécurité :
     * - Au moins 8 caractères
     * - Au moins une lettre majuscule
     * - Au moins une lettre minuscule
     * - Au moins un chiffre
     * - Au moins un caractère spécial
     *
     * @param string $plain Le mot de passe en clair
     * @return array|null Tableau de messages d'erreur (critères non respectés) ou null si valide
     */
    public static function validatePasswordStrength(string $plain): ?array
    {
        $errors = [];

        // Au moins 8 caractères
        if (strlen($plain) < 8) {
            $errors[] = 'Au moins 8 caractères';
        }

        // Au moins une lettre majuscule
        if (!preg_match('/[A-Z]/', $plain)) {
            $errors[] = 'Une lettre majuscule';
        }

        // Au moins une lettre minuscule
        if (!preg_match('/[a-z]/', $plain)) {
            $errors[] = 'Une lettre minuscule';
        }

        // Au moins un chiffre
        if (!preg_match('/[0-9]/', $plain)) {
            $errors[] = 'Un chiffre';
        }

        // Au moins un caractère spécial
        if (!preg_match('/[^A-Za-z0-9]/', $plain)) {
            $errors[] = 'Un caractère spécial';
        }

        return empty($errors) ? null : $errors;
    }

    /**
     * Valide que le mot de passe et sa confirmation correspondent
     *
     * Compare de manière sécurisée (timing-safe) le mot de passe et sa confirmation.
     *
     * @param string $plain Le mot de passe original
     * @param string $confirm Le mot de passe de confirmation
     * @return string|null Message d'erreur ou null si les mots de passe correspondent
     */
    public static function validatePasswordConfirmation(string $plain, string $confirm): ?string
    {
        if (!hash_equals($plain, $confirm)) {
            return 'Les mots de passe ne correspondent pas.';
        }
        return null;
    }

    /**
     * Valide le format d'un hash Argon2id PHC
     *
     * Vérifie que le hash correspond au format Argon2id standard (PHC format).
     * Format attendu : $argon2id$v=19$m=65536,t=4,p=1$base64salt$base64hash
     *
     * @param string $hash Le hash à valider
     * @return string|null Message d'erreur ou null si le format est valide
     */
    public static function validateArgon2idPHC(string $hash): ?string
    {
        if (!preg_match(self::RE_ARGON2, $hash)) {
            return 'Le format du hash Argon2id est invalide.';
        }
        return null;
    }

    /**
     * Nettoie un token base64url
     *
     * Supprime les espaces et le padding '=' éventuel.
     * Les tokens base64url n'utilisent pas de padding pour être URL-safe.
     *
     * @param string $s Le token à nettoyer
     * @return string Le token nettoyé
     */
    public static function sanitizeBase64UrlToken(string $s): string
    {
        // trim + suppression d'espaces invisibles
        $s = trim($s);
        // on retire les éventuels '=' de padding s'ils arrivent
        return rtrim($s, '=');
    }

    /**
     * Valide un token au format base64url
     *
     * Vérifie qu'un token :
     * - N'est pas vide
     * - Ne contient que des caractères base64url (A-Z, a-z, 0-9, -, _)
     * - Respecte les contraintes de longueur min/max
     *
     * Par défaut, un token de 32 octets fait ~43 caractères en base64url sans padding.
     *
     * @param string $s Le token à valider
     * @param int $min Longueur minimale (défaut: 24)
     * @param int $max Longueur maximale (défaut: 128)
     * @param string $label Label pour le message d'erreur (défaut: 'Le token')
     * @return string|null Message d'erreur ou null si valide
     */
    public static function validateBase64UrlToken(
        string $s,
        int $min = 24,
        int $max = 128,
        string $label = 'Le token',
    ): ?string {
        if ($s === '') {
            return "$label est requis.";
        }
        if (!preg_match('/^[A-Za-z0-9\-_]+$/', $s)) {
            return "$label a un format invalide.";
        }
        $len = strlen($s);
        if ($len < $min || $len > $max) {
            return "$label a une longueur invalide.";
        }
        return null;
    }

    /* ===============================
     *  Outils ponctuels
     * =============================== */
    public static function validateRegex(
        string $s,
        string $regex,
        string $label = 'La valeur',
    ): ?string {
        if (!preg_match($regex, $s)) {
            return "$label a un format invalide.";
        }
        return null;
    }
}
