<?php

namespace Teslapp\Controllers\Auth;

use Teslapp\Models\Auth\RememberTokenRepository;
use Teslapp\Models\Database;
use Teslapp\Models\Shared\Exceptions\TeslaApiException;
use Teslapp\Models\Shared\TeslaApi\TeslaHttpClient;
use Teslapp\Utils\RememberToken;

final class AuthCallbackController
{
    public function callback(): void
    {
        parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY) ?? '', $p);
        $code = $p['code'] ?? null;

        if (!is_string($code) || $code === '') {
            $status = 'error';
            $error = 'missing_code';
            require_once __DIR__ . '/../../Views/Auth/auth_callback.php';
            return;
        }

        try {
            TeslaHttpClient::exchangeCodeForUserToken($code);

            // exchangeCodeForUserToken() populates $_SESSION['user_id'] on success.
            // Issue a remember-me token so the user stays logged in across browser restarts.
            $userId = $_SESSION['user_id'] ?? null;
            if (is_string($userId) && $userId !== '') {
                $rememberToken = new RememberToken(new RememberTokenRepository(Database::pdo()));
                $rememberToken->issue($userId);
            }

            $status = 'success';
            $error = null;
        } catch (TeslaApiException $e) {
            error_log('OAuth code exchange failed: ' . $e->getMessage());
            $status = 'error';
            $error = 'token_exchange_failed';
        }

        require_once __DIR__ . '/../../Views/Auth/auth_callback.php';
    }
}
