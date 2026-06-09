<?php

namespace Teslapp\Controllers\Auth;

use Teslapp\Models\Shared\Exceptions\TeslaApiException;
use Teslapp\Models\Shared\TeslaApi\TeslaHttpClient;

final class AuthCallbackController
{
    public function callback(): void
    {
        parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY) ?? '', $p);
        $code = $p['code'] ?? null;
        $state = $p['state'] ?? null;

        // Anti-CSRF: the state echoed back by Tesla must match the one stored at /auth.
        // Consume it (single use) before touching the authorization code.
        $expectedState = $_SESSION['oauth_state'] ?? null;
        unset($_SESSION['oauth_state']);

        if (
            !is_string($state) ||
            !is_string($expectedState) ||
            !hash_equals($expectedState, $state)
        ) {
            $status = 'error';
            $error = 'invalid_state';
            require __DIR__ . '/../../Views/Auth/auth_callback.php';
            return;
        }

        if (!is_string($code) || $code === '') {
            $status = 'error';
            $error = 'missing_code';
            require __DIR__ . '/../../Views/Auth/auth_callback.php';
            return;
        }

        try {
            TeslaHttpClient::exchangeCodeForUserToken($code);
            $status = 'success';
            $error = null;
        } catch (TeslaApiException $e) {
            error_log('OAuth code exchange failed: ' . $e->getMessage());
            $status = 'error';
            $error = 'token_exchange_failed';
        }

        require __DIR__ . '/../../Views/Auth/auth_callback.php';
    }
}
