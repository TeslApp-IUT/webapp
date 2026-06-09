<?php

namespace Teslapp\Controllers\Auth;

use Teslapp\Utils\Csrf;
use Teslapp\Utils\Http;

/**
 * Authentification Controller
 *
 * This controller handles authentication through Tesla
 *
 */
final class AuthController
{
    public function auth(): void
    {
        match ($_SERVER['REQUEST_METHOD']) {
            'GET' => $this->handleGet(),
            'POST' => $this->handlePost(),
            default => $this->methodNotAllowed(),
        };
    }

    /**
     * Logs the user out: clears the local session and tells the browser to drop its
     * site data. The Tesla token is NOT revoked (partial logout — the user can log
     * back in without MFA while the refresh token is still valid).
     */
    public function logout(): never
    {
        // Logout changes state → require a valid CSRF token (the header forms POST it).
        Csrf::requireValid('/site/home');

        // Clear the local session (the Tesla token is intentionally left intact).
        $_SESSION = [];
        session_destroy();

        // OWASP Session Management: have the browser drop cookies/storage/cache too.
        header('Clear-Site-Data: "cookies", "storage", "cache"');

        Http::redirect('/site/home');
    }

    private function handleGet(): void
    {
        // Anti-CSRF state for the OAuth flow: generated here, stored in the session,
        // injected into the /authorize URL by the view, and verified at the callback.
        $state = bin2hex(random_bytes(32));
        $_SESSION['oauth_state'] = $state;

        // PKCE (RFC 7636): keep the verifier server-side, send only the S256 challenge.
        // Protects the authorization code as it transits the browser/redirect.
        $codeVerifier = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $_SESSION['oauth_code_verifier'] = $codeVerifier;
        $codeChallenge = rtrim(
            strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'),
            '=',
        );

        // Nonce: echoed back inside the id_token and checked at the callback (anti-replay).
        $nonce = bin2hex(random_bytes(16));
        $_SESSION['oauth_nonce'] = $nonce;

        require_once __DIR__ . '/../../Views/Auth/auth.php';
    }

    private function handlePost(): void
    {
        http_response_code(401);
        if (!empty($_SESSION['access_token'])) {
            http_response_code(200);
        }
    }

    private function methodNotAllowed(): never
    {
        http_response_code(405);
        exit();
    }
}
