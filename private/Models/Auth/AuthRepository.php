<?php

declare(strict_types=1);

namespace Teslapp\Models\Auth;

use PDO;
use PDOException;
use Teslapp\Models\DatabaseException;

/**
 * Persists the Tesla OAuth identity: the user (keyed by the Tesla `sub`), the decoded
 * id_token claims (`jwt` table) and the encrypted access/refresh tokens (`oauth2_token`).
 *
 * The user's identity is the Tesla `sub` claim, so `users.id = sub` and there is no
 * separate application user id.
 */
final readonly class AuthRepository
{
    public function __construct(private PDO $pdo) {}

    /**
     * Inserts the user keyed by the Tesla `sub`, or refreshes its email on conflict.
     */
    public function ensureUser(
        string $userId,
        string $email,
        string $firstName,
        string $lastName,
        string $avatarUrl = '',
    ): void {
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO users (id, email, first_name, last_name, avatar_url) VALUES (:id, :email, :first_name, :last_name, :avatar_url)
                 ON CONFLICT (id) DO UPDATE SET email = EXCLUDED.email, updated_at = now(), first_name = EXCLUDED.first_name, last_name = EXCLUDED.last_name, avatar_url = EXCLUDED.avatar_url',
            );
            $stmt->execute([
                ':id' => $userId,
                ':email' => $email,
                ':first_name' => $firstName,
                ':last_name' => $lastName,
                ':avatar_url' => $avatarUrl ?: null,
            ]);
        } catch (PDOException $e) {
            throw new DatabaseException("Failed to upsert user $userId", previous: $e);
        }
    }

    /**
     * Stores the decoded id_token claims, upserting on the unique `sub`.
     *
     * @param int $authTime Unix timestamp of the original authentication.
     * @param int $exp Unix timestamp at which the token expires.
     * @param int $iat Unix timestamp at which the token was issued.
     */
    public function saveJwt(
        string $sub,
        string $iss,
        string $aud,
        int $authTime,
        int $exp,
        int $iat,
    ): void {
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO jwt (id, iss, sub, aud, auth_time, exp, iat, updated_at)
                 VALUES (
                     gen_random_uuid(), :iss, :sub, :aud,
                     to_timestamp(:auth_time), to_timestamp(:exp), to_timestamp(:iat), now()
                 )
                 ON CONFLICT (sub) DO UPDATE SET
                     iss = EXCLUDED.iss,
                     aud = EXCLUDED.aud,
                     auth_time = EXCLUDED.auth_time,
                     exp = EXCLUDED.exp,
                     iat = EXCLUDED.iat,
                     updated_at = now()',
            );
            $stmt->execute([
                ':iss' => $iss,
                ':sub' => $sub,
                ':aud' => $aud,
                ':auth_time' => $authTime,
                ':exp' => $exp,
                ':iat' => $iat,
            ]);
        } catch (PDOException $e) {
            throw new DatabaseException("Failed to save jwt for user $sub", previous: $e);
        }
    }

    /**
     * Stores the encrypted OAuth tokens for the user, upserting on the unique `user_id`.
     *
     * @param int $accessExpiresAt Unix timestamp at which the access token expires.
     * @param int $refreshExpiresAt Unix timestamp at which the refresh token expires.
     */
    public function saveOAuthToken(
        string $userId,
        string $accessEncrypted,
        string $accessNonce,
        int $accessExpiresAt,
        string $refreshEncrypted,
        string $refreshNonce,
        int $refreshExpiresAt,
    ): void {
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO oauth2_token (
                     id, user_id,
                     access_token_encrypted, access_token_nonce, access_token_expired_at,
                     refresh_token_encrypted, refresh_token_nonce, refresh_token_expired_at
                 )
                 VALUES (
                     gen_random_uuid(), :user_id,
                     :access_enc, :access_nonce, to_timestamp(:access_exp),
                     :refresh_enc, :refresh_nonce, to_timestamp(:refresh_exp)
                 )
                 ON CONFLICT (user_id) DO UPDATE SET
                     access_token_encrypted = EXCLUDED.access_token_encrypted,
                     access_token_nonce = EXCLUDED.access_token_nonce,
                     access_token_expired_at = EXCLUDED.access_token_expired_at,
                     refresh_token_encrypted = EXCLUDED.refresh_token_encrypted,
                     refresh_token_nonce = EXCLUDED.refresh_token_nonce,
                     refresh_token_expired_at = EXCLUDED.refresh_token_expired_at,
                     updated_at = now()',
            );
            $stmt->execute([
                ':user_id' => $userId,
                ':access_enc' => $accessEncrypted,
                ':access_nonce' => $accessNonce,
                ':access_exp' => $accessExpiresAt,
                ':refresh_enc' => $refreshEncrypted,
                ':refresh_nonce' => $refreshNonce,
                ':refresh_exp' => $refreshExpiresAt,
            ]);
        } catch (PDOException $e) {
            throw new DatabaseException(
                "Failed to save oauth2 token for user $userId",
                previous: $e,
            );
        }
    }

    /**
     * Returns the latest stored OAuth token row for the user (still encrypted), or null.
     *
     * @return array<string, mixed>|null
     */
    public function getLatestCredentials(string $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT access_token_encrypted, access_token_nonce, access_token_expired_at,
                    refresh_token_encrypted, refresh_token_nonce, refresh_token_expired_at
             FROM oauth2_token
             WHERE user_id = :user_id
             ORDER BY updated_at DESC
             LIMIT 1',
        );
        $stmt->execute([':user_id' => $userId]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function isUserInDatabase(string $sub_id): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1
                   FROM users
                   WHERE id = :sub_id',
        );
        $stmt->execute([':sub_id' => $sub_id]);
        $row = $stmt->fetch();
        return $row !== false;
    }

    /**
     * Returns the full user row, or null if not found.
     *
     * @return array{id: string, email: string, first_name: string, last_name: string, avatar_url: string|null}|null
     */
    public function getUserById(string $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, email, first_name, last_name, avatar_url FROM users WHERE id = :id LIMIT 1',
        );
        $stmt->execute([':id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
    }

    /**
     * Updates the user's editable profile fields.
     */
    public function updateUser(
        string $userId,
        string $email,
        string $firstName,
        string $lastName,
    ): void {
        try {
            $stmt = $this->pdo->prepare(
                'UPDATE users
                 SET email = :email, first_name = :first_name, last_name = :last_name, updated_at = now()
                 WHERE id = :id',
            );
            $stmt->execute([
                ':id' => $userId,
                ':email' => $email,
                ':first_name' => $firstName,
                ':last_name' => $lastName,
            ]);
        } catch (PDOException $e) {
            throw new DatabaseException("Failed to update user $userId", previous: $e);
        }
    }

    /**
     * Permanently deletes a user and all cascading rows (tokens, vehicles, etc.).
     */
    public function deleteUser(string $userId): void
    {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM users WHERE id = :id');
            $stmt->execute([':id' => $userId]);
        } catch (PDOException $e) {
            throw new DatabaseException("Failed to delete user $userId", previous: $e);
        }
    }

    /**
     * Returns the user's first and last name, or null if the user is not found.
     *
     * @return array{first_name: string, last_name: string}|null
     */
    public function getUserName(string $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT first_name, last_name FROM users WHERE id = :id LIMIT 1',
        );
        $stmt->execute([':id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false
            ? [
                'first_name' => (string) $row['first_name'],
                'last_name' => (string) $row['last_name'],
            ]
            : null;
    }

    /**
     * Returns the user's first, last name, email and avatar url, or null if the user is not found.
     *
     * @return array{first_name: string, last_name: string, email: string, avatar_url: string}|null
     */
    public function getCredentials(string $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT first_name, last_name, email, avatar_url FROM users WHERE id = :id LIMIT 1',
        );
        $stmt->execute([':id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false
            ? [
                'first_name' => (string) $row['first_name'],
                'last_name' => (string) $row['last_name'],
                'email' => (string) $row['email'],
                'avatar_url' => (string) $row['avatar_url'],
            ]
            : null;
    }
}
