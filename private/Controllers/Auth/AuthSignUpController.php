<?php

namespace Teslapp\Controllers\Auth;

use PDO;

final class AuthSignUpController
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function signup(): void
    {
        require_once __DIR__ . '/../../Views/Auth/auth_signup.php';
    }
}
