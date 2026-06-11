<?php

declare(strict_types=1);

namespace Teslapp\Controllers\Auth;

use Teslapp\Models\Auth\AuthRepository;
use Teslapp\Models\Auth\ImpersonationRepository;
use Teslapp\Models\Shared\TokenCipher;
use Teslapp\Utils\Csrf;
use Teslapp\Utils\Flash;

final class AuthImpersonateController
{
    public function __construct(
        private readonly ImpersonationRepository $repo,
        private readonly AuthRepository $authRepo,
        private readonly TokenCipher $cipher,
    ) {}

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

        // Validate target exists and is not the developer themselves
        $realId = (string) $_SESSION['real_user_id'];
        $all = $this->repo->listUsersExcept($realId);
        $valid = array_filter($all, static fn(array $u): bool => $u['id'] === $targetId);
        if (empty($valid)) {
            header('Location: /auth/impersonate', true, 302);
            exit();
        }

        // Load target user's OAuth tokens from DB
        $credentials = $this->authRepo->getLatestCredentials($targetId);
        if ($credentials === null) {
            Flash::set('errors', ["Cet utilisateur n'a pas de jetons OAuth enregistrés."]);
            header('Location: /auth/impersonate', true, 302);
            exit();
        }

        // Stash the developer's tokens once (guard against nested impersonation overwriting them)
        if (!array_key_exists('real_access_token', $_SESSION)) {
            $_SESSION['real_access_token'] = $_SESSION['access_token'] ?? null;
            $_SESSION['real_refresh_token'] = $_SESSION['refresh_token'] ?? null;
            $_SESSION['real_access_token_expires_at'] =
                $_SESSION['access_token_expires_at'] ?? null;
        }

        // Swap in the target user's decrypted tokens
        $_SESSION['access_token'] = $this->cipher->decrypt(
            $credentials['access_token_encrypted'],
            $credentials['access_token_nonce'],
        );
        $_SESSION['refresh_token'] = $this->cipher->decrypt(
            $credentials['refresh_token_encrypted'],
            $credentials['refresh_token_nonce'],
        );
        // Force a refresh on the next API call: the stored token may be expired and the
        // buffer-adjusted expiry constant is private to TeslaHttpClient.
        $_SESSION['access_token_expires_at'] = 0;

        $_SESSION['user_id'] = $targetId;

        // Keep the header in sync with the impersonated user's identity
        $targetUser = $this->authRepo->getUserById($targetId);
        if ($targetUser !== null) {
            $_SESSION['user_display_name'] =
                $targetUser['first_name'] . ' ' . $targetUser['last_name'];
            $_SESSION['user']['email'] = $targetUser['email'];
        }

        header('Location: /dashboard', true, 302);
        exit();
    }

    public function stop(): never
    {
        if (!isset($_SESSION['real_user_id'])) {
            header('Location: /', true, 302);
            exit();
        }

        Csrf::requireValid('/auth/impersonate');

        // Restore developer's identity and tokens
        $_SESSION['user_id'] = $_SESSION['real_user_id'];
        $_SESSION['access_token'] = $_SESSION['real_access_token'] ?? null;
        $_SESSION['refresh_token'] = $_SESSION['real_refresh_token'] ?? null;
        $_SESSION['access_token_expires_at'] = $_SESSION['real_access_token_expires_at'] ?? null;

        // Restore the real developer's display name and email
        $realUser = $this->authRepo->getUserById($_SESSION['real_user_id']);
        if ($realUser !== null) {
            $_SESSION['user_display_name'] = $realUser['first_name'] . ' ' . $realUser['last_name'];
            $_SESSION['user']['email'] = $realUser['email'];
        }

        unset(
            $_SESSION['real_user_id'],
            $_SESSION['real_access_token'],
            $_SESSION['real_refresh_token'],
            $_SESSION['real_access_token_expires_at'],
        );

        header('Location: /auth/impersonate', true, 302);
        exit();
    }

    private function requireDeveloper(): void
    {
        if (($_SESSION['is_developer'] ?? false) !== true) {
            http_response_code(403);
            exit();
        }

        if (!isset($_SESSION['real_user_id'])) {
            $_SESSION['real_user_id'] = $_SESSION['user_id'];
        }
    }
}
