<?php

declare(strict_types=1);

namespace Teslapp\Utils;

/**
 * Utility class for validating and sanitizing user inputs
 *
 * Provides a set of methods to validate and clean incoming data
 * (forms, API, etc.). Each data type (email, token, etc.)
 * has dedicated sanitization and validation methods.
 *
 * Regular expressions are centralized as constants to ensure consistent
 * validation throughout the application.
 *
 * Usage pattern:
 * 1. Sanitize: cleans and normalizes the data (trim, lowercase, collapse spaces)
 * 2. Validate: checks conformity and returns an error message or null
 *
 * @package Teslapp\Utils
 */
final class Inputs
{
    /* ===============================
     *  Central regexes
     * =============================== */

    /**
     * Regular expression for email addresses
     * Standard format: local@domain.tld
     *
     * @var string
     */
    public const RE_EMAIL = '/^[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$/';

    /* ===============================
     *  Generic helpers
     * =============================== */

    /**
     * Collapses multiple whitespace characters into a single space
     *
     * Replaces any sequence of whitespace (spaces, tabs, newlines)
     * with a single space, then trims leading and trailing whitespace.
     *
     * @param string $s The string to process
     * @return string The string with normalized spaces
     */
    public static function collapseSpaces(string $s): string
    {
        // Replace any whitespace sequence with a single space
        $s = preg_replace('/\s+/u', ' ', $s);
        return trim($s);
    }

    /**
     * Cleans and normalizes a string
     *
     * Generic sanitization method applying several transformations:
     * - trim(): removes leading and trailing whitespace
     * - collapseSpaces(): normalizes multiple spaces (optional)
     * - mb_strtolower(): converts to UTF-8 lowercase (optional)
     *
     * @param string $s The string to clean
     * @param bool $lower Convert to lowercase (default: false)
     * @param bool $collapseSpaces Collapse multiple spaces (default: true)
     * @return string The cleaned string
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
     * Validates the length of a string
     *
     * Checks that the string length (in UTF-8 characters) respects
     * the defined min/max bounds. Returns a custom error message if invalid.
     *
     * @param string $s The string to validate
     * @param int|null $min Minimum required length (null = no minimum)
     * @param int|null $max Maximum allowed length (null = no maximum)
     * @param string $label Label used in the error message
     * @return string|null Error message or null if valid
     */
    public static function validateLength(
        string $s,
        ?int $min = null,
        ?int $max = null,
        string $label = 'The value',
    ): ?string {
        $len = mb_strlen($s, 'UTF-8');
        if ($min !== null && $len < $min) {
            return "$label must be at least $min characters.";
        }
        if ($max !== null && $len > $max) {
            return "$label must not exceed $max characters.";
        }
        return null;
    }

    /* ===============================
     *  Email
     * =============================== */

    /**
     * Sanitizes an email address
     *
     * Applies: trim, lowercase conversion, multiple space collapsing.
     *
     * @param string $s The email address to clean
     * @return string The cleaned email address
     */
    public static function sanitizeEmail(string $s): string
    {
        return self::sanitizeString($s, lower: true, collapseSpaces: true);
    }

    /**
     * Validates an email address
     *
     * Checks that the email:
     * - Is not empty
     * - Does not exceed the maximum length (RFC 5321: 254 characters)
     * - Matches the RE_EMAIL format (local@domain.tld)
     *
     * @param string $s The email address to validate
     * @param int $max Maximum allowed length (default: 254)
     * @return string|null Error message or null if valid
     */
    public static function validateEmail(string $s, int $max = 254): ?string
    {
        if ($s === '') {
            return 'Email is required.';
        }
        if (!preg_match(self::RE_EMAIL, $s)) {
            return 'Invalid email format.';
        }
        return self::validateLength($s, max: $max, label: 'Email');
    }

    /* ===============================
     *  Integer identifier (> 0) — nullable
     * =============================== */

    /**
     * Sanitizes and converts an ID to a positive integer
     *
     * Converts a string to an integer if it represents a valid positive number.
     * Returns null if the string is empty, non-numeric, or if the ID is <= 0.
     *
     * @param string|null $s The string representing the ID
     * @return int|null The ID as a positive integer or null
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
     * Validates an integer identifier
     *
     * Checks that an ID is either null (if nullable is accepted) or a strictly positive integer.
     *
     * @param int|null $id The identifier to validate
     * @param string $label Label for the error message (default: 'Identifier')
     * @return string|null Error message or null if valid
     */
    public static function validateIntId(?int $id, string $label = 'Identifier'): ?string
    {
        if ($id === null) {
            return null;
        } // nullable OK
        if ($id <= 0) {
            return "$label is invalid.";
        }
        return null;
    }

    /* ===============================
     *  Base64url token (OAuth state, CSRF, etc.)
     * =============================== */

    /**
     * Sanitizes a base64url token
     *
     * Removes whitespace and any '=' padding.
     * Base64url tokens do not use padding to remain URL-safe.
     *
     * @param string $s The token to clean
     * @return string The cleaned token
     */
    public static function sanitizeBase64UrlToken(string $s): string
    {
        // trim + removal of invisible whitespace
        $s = trim($s);
        // strip any '=' padding if present
        return rtrim($s, '=');
    }

    /**
     * Validates a base64url token
     *
     * Checks that a token:
     * - Is not empty
     * - Contains only base64url characters (A-Z, a-z, 0-9, -, _)
     * - Respects the min/max length constraints
     *
     * By default, a 32-byte token is ~43 characters in base64url without padding.
     *
     * @param string $s The token to validate
     * @param int $min Minimum length (default: 24)
     * @param int $max Maximum length (default: 128)
     * @param string $label Label for the error message (default: 'The token')
     * @return string|null Error message or null if valid
     */
    public static function validateBase64UrlToken(
        string $s,
        int $min = 24,
        int $max = 128,
        string $label = 'The token',
    ): ?string {
        if ($s === '') {
            return "$label is required.";
        }
        if (!preg_match('/^[A-Za-z0-9\-_]+$/', $s)) {
            return "$label has an invalid format.";
        }
        return self::validateLength($s, min: $min, max: $max, label: $label);
    }

    /* ===============================
     *  One-off utilities
     * =============================== */

    /**
     * Validates a string against an arbitrary regular expression.
     *
     * @param string $s The string to validate
     * @param string $regex The PCRE pattern to apply
     * @param string $label Label for the error message (default: 'The value')
     * @return string|null Error message or null if valid
     */
    public static function validateRegex(
        string $s,
        string $regex,
        string $label = 'The value',
    ): ?string {
        if (!preg_match($regex, $s)) {
            return "$label has an invalid format.";
        }
        return null;
    }
}
