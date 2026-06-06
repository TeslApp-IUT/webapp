<?php

namespace Teslapp\Controllers\Auth;

final class AuthSignUpController
{
    public function signup(): void
    {
        require_once __DIR__ . '/../../Views/Auth/auth_signup.php';
    }
}
