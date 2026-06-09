<?php

namespace Teslapp\Controllers\Auth;

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

    private function handleGet(): void
    {
        // Anti-CSRF state for the OAuth flow: generated here, stored in the session,
        // injected into the /authorize URL by the view, and verified at the callback.
        $state = bin2hex(random_bytes(32));
        $_SESSION['oauth_state'] = $state;

        require_once __DIR__ . '/../../Views/Auth/auth.php';
    }

    private function handlePost(): void
    {
        http_response_code(401);
        if ($_SESSION['access_token']) {
            http_response_code(200);
        }
    }

    private function methodNotAllowed(): never
    {
        http_response_code(405);
        exit();
    }
}
