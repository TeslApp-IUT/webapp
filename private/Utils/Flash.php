<?php

declare(strict_types=1);

namespace Teslapp\Utils;

/**
 * Class for managing in-session flash messages
 *
 * Provides a simplified interface for storing and retrieving
 * temporary data within a session, particularly useful for error messages,
 * success messages, and preserving form data between requests.
 *
 * Flash messages are typically used for:
 * - Displaying confirmation messages after an action (locking, Tesla login, etc.)
 * - Persisting validation errors across requests
 * - Preserving data entered in a form after redirection
 *
 * This class uses $_SESSION as its storage backend and provides
 * "consume" methods to automatically retrieve and delete
 * data after reading (the "flash" pattern).
 *
 * @package Teslapp\Utils
 */
final class Flash
{
    /**
     * Stores a value in the session under a given key
     *
     * Allows you to set any value (string, array, etc.)
     * in the session for later use.
     *
     * Example usage:
     * - Flash::set('errors', ['Invalid email'])
     * - Flash::set('success', 'Vehicle locked')
     * - Flash::set('old', $_POST)
     *
     * @param string $key Data identification key
     * @param mixed $value Value to store
     * @return void
     */
    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Retrieves a value from the session without deleting it
     *
     * Reads a value from the session and returns a default value
     * if the key does not exist. The value remains in the session after being read.
     *
     * Used when you want to view a value without consuming it.
     *
     * @param string $key Data identification key
     * @param mixed $default Value returned if the key does not exist (default: null)
     * @return mixed The value found or the default value
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Retrieves a value from the session and immediately removes it
     *
     * Implements the "flash message" pattern: reads a value once
     * and then removes it from the session. Perfect for error
     * or success messages that should only be displayed once.
     *
     * Typical usage example:
     * ```php
     * // In the controller (after validation)
     * Flash::set('errors', ['Invalid email']);
     * Http::redirect('/site/home');
     *
     * // In the view
     * $errors = Flash::consume('errors', []);
     * // The $errors variable contains the array, and it is removed from the session
     * ```
     *
     * @param string $key Data identification key
     * @param mixed $default Value returned if the key does not exist (default: null)
     * @return mixed The found value or the default value
     */
    public static function consume(string $key, mixed $default = null): mixed
    {
        $val = $_SESSION[$key] ?? $default;
        unset($_SESSION[$key]);
        return $val;
    }

    /**
     * Retrieves and removes multiple values at once
     *
     * A variant of `consume()` that allows you to retrieve multiple keys
     * simultaneously and remove them from the session.
     *
     * Returns an associative array containing the retrieved values,
     * with `null` for keys that do not exist.
     *
     * Example usage:
     * ```php
     * $data = Flash::consumeMany(['errors', 'success', 'old']);
     * // $data = ['errors' => [...], 'success' => null, 'old' => [...]]
     * ```
     *
     * @param list<string> $keys Array of keys to retrieve and delete
     * @return array<string, mixed> Associative array key => value
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
     * Adds one or more values to an existing array in the session
     *
     * If the key does not exist, creates a new array.
     * If the key exists and contains an array, merges the values.
     * If the key exists but is not an array, converts it to an array.
     *
     * Useful for accumulating multiple errors or successive messages.
     *
     * Example usage:
     * ```php
     * Flash::push('errors', 'Invalid email');
     * Flash::push('errors', 'Password too short');
     * // $_SESSION['errors'] = ['Invalid email', 'Password too short']
     * ```
     *
     * @param string $key Array key
     * @param mixed $value Value(s) to add (can be an array or a single value)
     * @return void
     */
    public static function push(string $key, mixed $value): void
    {
        $cur = $_SESSION[$key] ?? [];
        $_SESSION[$key] = array_merge((array) $cur, (array) $value);
    }
}
