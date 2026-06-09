<?php

declare(strict_types=1);

namespace Teslapp\Controllers\Auth;

use Teslapp\Models\Auth\ImpersonationRepository;
use Teslapp\Utils\Csrf;

final class AuthImpersonateController
{
    public function __construct(private readonly ImpersonationRepository $repo) {}

    public function show(): void
    {
        $this->requireDeveloper();
        $realId = (string) $_SESSION['real_user_id'];
        $users = $this->repo->listUsersExcept($realId);
        require_once __DIR__ . '/../../Views/Auth/auth_impersonate.php';
    }

    public function start(): never
    {
        $this->requireDeveloper();
        Csrf::requireValid('/auth/impersonate');

        $targetId = $_POST['user_id'] ?? '';
        if (!is_string($targetId) || $targetId === '') {
            header('Location: /auth/impersonate', true, 302);
            exit();
        }

        // Validate the target exists in DB (not just blindly trusting POST input)
        $realId = (string) $_SESSION['real_user_id'];
        $all = $this->repo->listUsersExcept($realId);
        $valid = array_filter($all, static fn(array $u): bool => $u['id'] === $targetId);
        if (empty($valid)) {
            header('Location: /auth/impersonate', true, 302);
            exit();
        }

        $_SESSION['user_id'] = $targetId;
        unset($_SESSION['selected_vin']);

        header('Location: /vehicle/select', true, 302);
        exit();
    }

    public function stop(): never
    {
        if (!isset($_SESSION['real_user_id'])) {
            header('Location: /', true, 302);
            exit();
        }

        Csrf::requireValid('/auth/impersonate');

        $_SESSION['user_id'] = $_SESSION['real_user_id'];
        unset($_SESSION['selected_vin']);

        header('Location: /auth/impersonate', true, 302);
        exit();
    }

    private function requireDeveloper(): void
    {
        if (($_SESSION['is_developer'] ?? false) !== true) {
            http_response_code(403);
            exit();
        }

        // Ensure real_user_id is always set while on impersonation pages
        if (!isset($_SESSION['real_user_id'])) {
            $_SESSION['real_user_id'] = $_SESSION['user_id'];
        }
    }
}
