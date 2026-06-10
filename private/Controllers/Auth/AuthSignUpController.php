<?php

declare(strict_types=1);

namespace Teslapp\Controllers\Auth;

use Teslapp\Models\Auth\AuthRepository;
use Teslapp\Models\Shared\TeslaApi\TeslaHttpClient;
use Teslapp\Utils\Csrf;
use Teslapp\Utils\Http;
use Teslapp\Utils\Inputs;
use Teslapp\Utils\RememberToken;

/**
 * Finalises a first-time Tesla login. The OAuth callback stashed the pending identity in the
 * session (`signup_tmp_*`) without creating any database row; here the visitor confirms/edits
 * their details, and only on a valid submit do we create the `users` row, persist the tokens
 * and log them in.
 */
final readonly class AuthSignUpController
{
    public function __construct(
        private AuthRepository $authRepository,
        private RememberToken $rememberToken,
    ) {}

    public function signup(): void
    {
        // Reachable only mid-OAuth: the callback must have stashed a pending identity.
        if (empty($_SESSION['signup_tmp_sub'])) {
            Http::redirect('/auth');
        }

        match ($_SERVER['REQUEST_METHOD']) {
            'POST' => $this->handlePost(),
            default => $this->renderForm([
                'firstName' => (string) ($_SESSION['signup_tmp_first_name'] ?? ''),
                'lastName' => (string) ($_SESSION['signup_tmp_last_name'] ?? ''),
                'email' => (string) ($_SESSION['signup_tmp_email'] ?? ''),
            ]),
        };
    }

    private function handlePost(): void
    {
        $firstName = Inputs::sanitizeString((string) ($_POST['firstName'] ?? ''));
        $lastName = Inputs::sanitizeString((string) ($_POST['lastName'] ?? ''));
        $email = Inputs::sanitizeEmail((string) ($_POST['email'] ?? ''));

        $pendingUser = ['firstName' => $firstName, 'lastName' => $lastName, 'email' => $email];

        if (!Csrf::checkFromPost()) {
            $this->renderForm($pendingUser, [
                'general' => 'Le formulaire a expiré. Veuillez réessayer.',
            ]);
            return;
        }

        $errors = array_filter([
            'firstName' => Inputs::validateLength(
                $firstName,
                min: 1,
                max: 100,
                label: 'First name',
            ),
            'lastName' => Inputs::validateLength($lastName, min: 1, max: 100, label: 'Last name'),
            'email' => Inputs::validateEmail($email),
        ]);

        if ($errors !== []) {
            $this->renderForm($pendingUser, $errors);
            return;
        }

        $sub = (string) $_SESSION['signup_tmp_sub'];
        $accessToken = (string) ($_SESSION['access_token'] ?? '');
        $refreshToken = (string) ($_SESSION['refresh_token'] ?? '');

        // The tokens are cached in the session by the callback; if they've been lost (e.g. the
        // session idled out between the callback and this submit), restart the OAuth flow.
        if ($accessToken === '' || $refreshToken === '') {
            Http::redirect('/auth');
        }

        // 1. Create the user row FIRST — jwt.sub / oauth2_token.user_id FK onto users.id.
        $this->authRepository->ensureUser($sub, $email, $firstName, $lastName);

        // 2. Persist the OAuth credentials gathered during the callback.
        $claims = $_SESSION['signup_tmp_claims'] ?? null;
        TeslaHttpClient::persistUserCredentials(
            $sub,
            is_array($claims) ? $claims : null,
            $accessToken,
            (int) ($_SESSION['signup_tmp_access_expires_at'] ?? 0),
            $refreshToken,
            (int) ($_SESSION['signup_tmp_refresh_expires_at'] ?? 0),
        );

        // 3. Log the user in exactly like a returning user would be.
        $this->rememberToken->issue($sub);
        $this->cleanupSignupSession();
        $_SESSION['user_id'] = $sub;
        session_regenerate_id(true);

        Http::redirect('/dashboard');
    }

    /**
     * @param array{firstName: string, lastName: string, email: string} $pendingUser
     * @param array<string, string> $errors field name => message ('general' for a form-wide error)
     */
    private function renderForm(array $pendingUser, array $errors = []): void
    {
        $profilePicture = (string) ($_SESSION['signup_tmp_profile_picture'] ?? '');
        $csrfToken = (string) ($_SESSION['csrf_token'] ?? '');
        require_once __DIR__ . '/../../Views/Auth/auth_signup.php';
    }

    /**
     * Drops every `signup_tmp_*` key once the account exists, leaving the live session
     * (user_id, access/refresh tokens) intact so the user is logged in.
     */
    private function cleanupSignupSession(): void
    {
        foreach (array_keys($_SESSION) as $key) {
            if (str_starts_with((string) $key, 'signup_tmp_')) {
                unset($_SESSION[$key]);
            }
        }
    }
}
