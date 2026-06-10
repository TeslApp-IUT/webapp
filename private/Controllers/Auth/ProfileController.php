<?php

namespace Teslapp\Controllers\Auth;

class ProfileController
{
    public function __construct(private AuthRepository $authRepository) {}

    public function profile(): void
    {
        require __DIR__ . '/../../Views/Auth/profile.php';
        return;
    }
}
