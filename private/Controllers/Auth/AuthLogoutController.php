<?php

declare(strict_types=1);

namespace Teslapp\Controllers\Auth;

use Teslapp\Utils\RememberToken;

final class AuthLogoutController
{
    public function __construct(private readonly RememberToken $rememberToken) {}

    public function logout(): never
    {
        $this->rememberToken->revoke();

        $_SESSION = [];
        session_destroy();

        header('Location: /', replace: true, response_code: 302);
        exit();
    }
}
