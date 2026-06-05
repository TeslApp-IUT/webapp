<?php

namespace Teslapp\Models\Auth;

use PDO;

final readonly class AuthRepository
{
    public function __construct(PDO $pdo) {
        // $pdo will be injected
    }

    public function getLatestCredentials()
}
