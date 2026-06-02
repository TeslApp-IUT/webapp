<?php

declare(strict_types=1);

namespace Teslapp\Utils;

/**
 * Class for managing CSRF (Cross-Site Request Forgery) tokens
 *
 * Protects the application against CSRF attacks by generating and validating
 * unique tokens for each user session. These tokens must be
 * included in all sensitive forms and verified on the server side.
 *
 * The token is stored in the session and compared with the one submitted via POST
 * using hash_equals() to guard against timing attacks.
 *
 * @package Teslapp\Utils
 */
final class Csrf
{
    /**
     * Generates and stores a CSRF token in the session if one does not already exist
     *
     * This method must be called at the beginning of every request requiring
     * CSRF protection (typically in the autoloader or router).
     *
     * The token is generated using random_bytes(32) and then converted to hexadecimal,
     * producing a cryptographically secure 64-character string.
     *
     * If the session is not started, this method starts it automatically.
     *
     * @return void
     */
    public static function ensureToken(): void
    {
        // Start the session if necessary
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Generate the token if it doesn't exist
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    /**
     * Checks the validity of the CSRF token submitted via POST
     *
     * Compares the token stored in the session with the one received in $_POST.
     * Uses hash_equals() for a timing-safe comparison that prevents
     * timing-based attacks.
     *
     * @param string $key Name of the POST field containing the token (default: 'csrf_token')
     * @return bool True if the token is valid, false otherwise
     */
    public static function checkFromPost(string $key = 'csrf_token'): bool
    {
        $sess = $_SESSION['csrf_token'] ?? '';
        $post = $_POST[$key] ?? '';

        // Check that both tokens exist and match
        return $sess && $post && hash_equals($sess, $post);
    }

    /**
     * Checks the CSRF token and redirects if validation fails
     *
     * Utility method that combines CSRF token validation with
     * automatic error handling and redirection.
     *
     * If validation fails:
     * - An error message is added to the flash messages
     * - The old form data may be preserved (if $keepOld = true)
     * - The user is redirected to the specified URL
     * - Script execution is stopped
     *
     * This method avoids duplication of CSRF validation code
     * across different controllers.
     *
     * @param string $redirectUrl Redirect URL in case of validation failure
     * @param bool $keepOld If true, retains POST data in flash messages (default: false)
     * @return void Redirects and stops execution if the token is invalid
     */
    public static function requireValid(string $redirectUrl, bool $keepOld = false): void
    {
        if (!self::checkFromPost()) {
            // User error message
            Flash::set('errors', ['The form has expired. Please try again.']);

            // Save the entered data if requested
            if ($keepOld) {
                Flash::set('old', $_POST);
            }

            // Redirection (Http::redirect already stops execution: return type `never`).
            Http::redirect($redirectUrl);
        }
    }
}
