<?php

declare(strict_types=1);

namespace Teslapp\Controllers\Auth;

use Teslapp\Models\Auth\AuthRepository;

class ProfileController
{
    public function __construct(private AuthRepository $authRepository) {}

    // ── GET /profile ──────────────────────────────────────────────
    public function profile(): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            header('Location: /login');
            exit();
        }

        $row = $this->authRepository->getUserById($userId);
        if (!$row) {
            header('Location: /login');
            exit();
        }

        // Shape expected by the view
        $user = [
            'firstName' => $row['first_name'],
            'lastName' => $row['last_name'],
            'email' => $row['email'],
        ];
        $profilePicture = $row['avatar_url'] ?? '';
        $csrfToken = $this->generateCsrfToken();
        $errors = [];

        require __DIR__ . '/../../Views/Auth/profile.php';
    }

    // ── POST /profile/update ──────────────────────────────────────
    public function update(): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            header('Location: /login');
            exit();
        }

        // CSRF check
        $submittedToken = $_POST['csrf_token'] ?? '';
        if (!$this->validateCsrfToken($submittedToken)) {
            $this->renderWithErrors($userId, ['general' => 'Session expirée, veuillez réessayer.']);
            return;
        }

        $firstName = trim($_POST['firstName'] ?? '');
        $lastName = trim($_POST['lastName'] ?? '');
        $email = trim($_POST['email'] ?? '');

        // Validation
        $errors = [];
        if ($firstName === '') {
            $errors['firstName'] = true;
        }
        if ($lastName === '') {
            $errors['lastName'] = true;
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = true;
        }

        if (!empty($errors)) {
            $this->renderWithErrors($userId, $errors, $firstName, $lastName, $email);
            return;
        }

        try {
            $this->authRepository->updateUser($userId, $email, $firstName, $lastName);
            $_SESSION['user_display_name'] = $firstName . ' ' . $lastName;
        } catch (\Throwable) {
            $this->renderWithErrors(
                $userId,
                ['general' => 'Une erreur est survenue, veuillez réessayer.'],
                $firstName,
                $lastName,
                $email,
            );
            return;
        }

        // PRG — redirect so a page refresh doesn't resubmit the form
        header('Location: /profile?saved=1');
        exit();
    }

    // ── GET /profile/delete ───────────────────────────────────────
    public function delete(): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            header('Location: /login');
            exit();
        }

        // CSRF check (passed as query param from the confirm link)
        $submittedToken = $_GET['csrf_token'] ?? '';
        if (!$this->validateCsrfToken($submittedToken)) {
            header('Location: /profile');
            exit();
        }

        $this->authRepository->deleteUser($userId);

        session_destroy();
        header('Location: /?deleted=1');
        exit();
    }

    // ── Helpers ───────────────────────────────────────────────────

    /**
     * Re-renders the profile view with validation errors and the values
     * the user had already typed (so they aren't lost on error).
     */
    private function renderWithErrors(
        string $userId,
        array $errors,
        string $firstName = '',
        string $lastName = '',
        string $email = '',
    ): void {
        $row = $this->authRepository->getUserById($userId);

        $user = [
            'firstName' => $firstName !== '' ? $firstName : $row['first_name'] ?? '',
            'lastName' => $lastName !== '' ? $lastName : $row['last_name'] ?? '',
            'email' => $email !== '' ? $email : $row['email'] ?? '',
        ];
        $profilePicture = $row['avatar_url'] ?? '';
        $csrfToken = $this->generateCsrfToken();

        require __DIR__ . '/../../Views/Auth/profile.php';
    }

    /** Generates a CSRF token, stores it in the session, and returns it. */
    private function generateCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /** Returns true only when the submitted token matches the one in the session. */
    private function validateCsrfToken(string $token): bool
    {
        $expected = $_SESSION['csrf_token'] ?? '';
        // Constant-time comparison to prevent timing attacks
        return $expected !== '' && hash_equals($expected, $token);
    }
}
