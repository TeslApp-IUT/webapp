<?php

namespace Teslapp\Controllers\Auth;

use Random\RandomException;
use Teslapp\Models\Auth\AuthRepository;
use Teslapp\Models\Shared\Exceptions\TeslaApiException;
use Teslapp\Models\Shared\TeslaApi\TeslaHttpClient;
use Teslapp\Utils\RememberToken;

final readonly class AuthCallbackController
{
    public function __construct(
        private AuthRepository $authRepository,
        private RememberToken $rememberToken,
    ) {}

    /**
     * @throws RandomException
     */
    public function callback(): void
    {
        parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY) ?? '', $p);
        $code = $p['code'] ?? null;
        $state = $p['state'] ?? null;

        // The popup posts this back to the opener, which navigates the main window there.
        // Existing users go straight to the app; brand-new users finish on the signup form.
        $redirect = null;

        // Anti-CSRF: the state echoed back by Tesla must match the one stored at /auth.
        // Consume it (single use) before touching the authorization code.
        $expectedState = $_SESSION['oauth_state'] ?? null;
        unset($_SESSION['oauth_state']);

        if (
            !is_string($state) ||
            !is_string($expectedState) ||
            !hash_equals($expectedState, $state)
        ) {
            $status = 'error';
            $error = 'invalid_state';
            require __DIR__ . '/../../Views/Auth/auth_callback.php';
            return;
        }

        if (!is_string($code) || $code === '') {
            $status = 'error';
            $error = 'missing_code';
            require_once __DIR__ . '/../../Views/Auth/auth_callback.php';
            return;
        }

        try {
            // Caches the tokens in the session but writes NOTHING to the database yet:
            // we don't know if this is a returning user or a first-time signup.
            $identity = TeslaHttpClient::exchangeCodeForUserToken($code);
            $sub = $identity['sub'];

            if ($sub === '') {
                throw new TeslaApiException('OAuth exchange returned no user identity (sub).');
            }

            if ($this->authRepository->isUserInDatabase($sub)) {
                // Returning user: persist the refreshed credentials and log in as usual.
                TeslaHttpClient::persistUserCredentials(
                    $sub,
                    $identity['claims'],
                    $identity['accessToken'],
                    $identity['accessExpiresAt'],
                    $identity['refreshToken'],
                    $identity['refreshExpiresAt'],
                );

                $_SESSION['user_id'] = $sub;

                // Store the display name in the session for the header.
                $userName = $this->authRepository->getUserName($sub);
                $_SESSION['user_display_name'] = trim(($userName['first_name'] ?? '') . ' ' . ($userName['last_name'] ?? ''));

                // Issue a remember-me token so the user stays logged in across browser restarts.
                $this->rememberToken->issue($sub);
            } else {
                // First-time user: no `users` row yet (so we can't persist tokens — FK constraint).
                // Pre-fill the signup form from the Tesla profile and stash everything in the
                // session under `signup_tmp_*`; the row + tokens are created on signup submit.
                $this->stashSignupSession($identity);
                $redirect = '/auth/signup';
            }

            $status = 'success';
            $error = null;
        } catch (TeslaApiException $e) {
            error_log('OAuth code exchange failed: ' . $e->getMessage());
            $status = 'error';
            $error = 'token_exchange_failed';
        }

        require_once __DIR__ . '/../../Views/Auth/auth_callback.php';
    }

    /**
     * Fetches the Tesla profile and stores the pending-signup identity in the session under
     * `signup_tmp_*` keys (consumed by AuthSignUpController). No database row is created here.
     *
     * @param array{sub: string, claims: array<string, mixed>|null, accessToken: string,
     *               accessExpiresAt: int, refreshToken: string, refreshExpiresAt: int} $identity
     *
     * @throws TeslaApiException
     */
    private function stashSignupSession(array $identity): void
    {
        $profile = TeslaHttpClient::getUserProfile();

        $fullName = trim((string) ($profile['full_name'] ?? ''));
        $firstName = $fullName;
        $lastName = '';
        $spacePos = strpos($fullName, ' ');
        if ($spacePos !== false) {
            $firstName = substr($fullName, 0, $spacePos);
            $lastName = trim(substr($fullName, $spacePos + 1));
        }

        $_SESSION['signup_tmp_sub'] = $identity['sub'];
        $_SESSION['signup_tmp_email'] = (string) ($profile['email'] ?? '');
        $_SESSION['signup_tmp_first_name'] = $firstName;
        $_SESSION['signup_tmp_last_name'] = $lastName;
        $profileImageUrl = (string) ($profile['profile_image_url'] ?? '');
        $_SESSION['signup_tmp_profile_picture'] = $profileImageUrl;
        $profileImageDisplay = '';
        if ($profileImageUrl !== '') {
            $imageData = @file_get_contents($profileImageUrl);
            if ($imageData !== false) {
                $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($imageData) ?: 'image/jpeg';
                $profileImageDisplay = 'data:' . $mime . ';base64,' . base64_encode($imageData);
            }
        }
        $_SESSION['signup_tmp_profile_picture_display'] = $profileImageDisplay;
        $_SESSION['signup_tmp_claims'] = $identity['claims'];
        $_SESSION['signup_tmp_access_expires_at'] = $identity['accessExpiresAt'];
        $_SESSION['signup_tmp_refresh_expires_at'] = $identity['refreshExpiresAt'];
    }
}
