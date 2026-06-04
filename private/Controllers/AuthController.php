<?php

declare(strict_types=1);

namespace Teslapp\Controllers;

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
        require_once __DIR__ . '/../Views/Auth/auth.php';
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
