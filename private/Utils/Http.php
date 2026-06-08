<?php

declare(strict_types=1);

namespace Teslapp\Utils;

/**
 * Utility class for HTTP operations
 *
 * Provides simplified methods for handling HTTP redirects
 * and other HTTP-related operations.
 *
 * This class uses the static pattern for easy access
 * from anywhere in the application.
 *
 * @package Teslapp\Utils
 */
final class Http
{
    /**
     * Performs an HTTP redirect and stops execution
     *
     * Sends an HTTP "Location" header to redirect the browser to
     * a new URL and immediately terminates script execution.
     *
     * The method uses status code 302 (Found), which indicates a
     * temporary redirect. This type of redirect is appropriate for
     * most application use cases (after form submission,
     * login, logout, etc.).
     *
     * The "never" return type indicates that this method never returns
     * (it always terminates with exit), which aids in static code analysis.
     *
     * Example usage:
     * ```php
     * // Simple redirect
     * Http::redirect('/site/home');
     *
     * // Redirect after processing
     * if ($success) {
     *     Flash::set('success', 'Vehicle locked');
     *     Http::redirect('/site/home');
     * }
     * ```
     *
     * @param string $url Destination URL (relative or absolute)
     * @return never This method never returns (exit)
     */
    public static function redirect(string $url): never
    {
        header('Location: ' . $url, true, 302);
        exit();
    }

    /**
     * Sends a JSON response with the given HTTP status code, then stops execution.
     *
     * Used by AJAX endpoints (vehicle commands) to answer the browser's fetch().
     *
     * @param array<string, mixed> $data
     */
    public static function json(array $data, int $status = 200): never
    {
        // Encode before sending the status/headers so an encoding failure can still
        // switch to a valid 500 JSON body instead of a half-sent response (cf.
        // erreurs-exceptions.md §6).
        try {
            $body = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            error_log('Http::json encode failed: ' . $e->getMessage());
            $status = 500;
            $body = '{"error":"Internal error"}';
        }

        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo $body;
        exit();
    }
}
