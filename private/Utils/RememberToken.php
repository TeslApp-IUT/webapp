<?php

declare(strict_types=1);

namespace Teslapp\Utils;

use Teslapp\Models\Auth\RememberTokenRepository;

/**
 * Manages the "remember me" persistent-login cookie.
 *
 * Flow:
 *   - On login with "remember me": call issue() to generate + store + emit the cookie.
 *   - On every request (index.php): if session is missing, call tryReAuth().
 *     On success, tryReAuth() returns the user_id and rotates the token automatically.
 *   - On logout: call revoke() or revokeAll().
 *
 * The raw 64-char hex token lives only in the cookie. The DB stores its SHA-256 hash.
 */
final readonly class RememberToken
{
    private const COOKIE_NAME = 'teslapp_remember';
    private const TTL_DAYS = 30;

    public function __construct(private RememberTokenRepository $repo) {}

    /**
     * Generates a new token, persists its hash, and sets the cookie.
     */
    public function issue(string $userId): void
    {
        $raw = bin2hex(random_bytes(32)); // 64-char hex
        $hash = hash('sha256', $raw);
        $expiresAt = time() + self::TTL_DAYS * 86400;

        $this->repo->insert($userId, $hash, $expiresAt);

        setcookie(self::COOKIE_NAME, $raw, [
            'expires' => $expiresAt,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    /**
     * Attempts to re-authenticate from the remember-me cookie.
     *
     * Returns the user_id on success (and rotates the token), or null if the
     * cookie is absent / expired / unknown.
     */
    public function tryReAuth(): ?string
    {
        $raw = $_COOKIE[self::COOKIE_NAME] ?? null;
        if (!is_string($raw) || $raw === '') {
            return null;
        }

        $hash = hash('sha256', $raw);
        $userId = $this->repo->findUserByHash($hash);

        if ($userId === null) {
            // Cookie present but unknown or expired — clear it
            $this->clearCookie();
            return null;
        }

        // Rotate: delete old token, issue a fresh one
        $this->repo->deleteByHash($hash);
        $this->issue($userId);

        return $userId;
    }

    /**
     * Revokes the current remember-me cookie (single device logout).
     */
    public function revoke(): void
    {
        $raw = $_COOKIE[self::COOKIE_NAME] ?? null;
        if (is_string($raw) && $raw !== '') {
            $this->repo->deleteByHash(hash('sha256', $raw));
        }
        $this->clearCookie();
    }

    /**
     * Revokes all remember-me tokens for a user (full logout from all devices).
     */
    public function revokeAll(string $userId): void
    {
        $this->repo->deleteAllForUser($userId);
        $this->clearCookie();
    }

    // ------------------------------------------------------------------

    private function clearCookie(): void
    {
        setcookie(self::COOKIE_NAME, '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}
