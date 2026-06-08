<?php

declare(strict_types=1);

namespace Teslapp\Models\Auth;

use PDO;
use PDOException;
use Teslapp\Models\DatabaseException;

/**
 * Persists remember-me tokens used for persistent login.
 *
 * The raw token never touches the database; only its SHA-256 hash is stored,
 * following the same principle as password hashing.
 */
final readonly class RememberTokenRepository
{
    public function __construct(private PDO $pdo) {}

    /**
     * Inserts a new remember-me token for the given user.
     *
     * @param int $expiresAt Unix timestamp at which the token expires.
     */
    public function insert(string $userId, string $tokenHash, int $expiresAt): void
    {
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO remember_tokens (user_id, token_hash, expires_at)
                 VALUES (:user_id, :token_hash, to_timestamp(:expires_at))',
            );
            $stmt->execute([
                ':user_id' => $userId,
                ':token_hash' => $tokenHash,
                ':expires_at' => $expiresAt,
            ]);
        } catch (PDOException $e) {
            throw new DatabaseException(
                "Failed to insert remember token for user $userId",
                previous: $e,
            );
        }
    }

    /**
     * Returns the user_id linked to a valid (non-expired) token hash, or null if not found.
     */
    public function findUserByHash(string $tokenHash): ?string
    {
        $stmt = $this->pdo->prepare(
            'SELECT user_id FROM remember_tokens
             WHERE token_hash = :token_hash
               AND expires_at > now()',
        );
        $stmt->execute([':token_hash' => $tokenHash]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? (string) $row['user_id'] : null;
    }

    /**
     * Deletes a specific token by its hash (used on rotation or explicit logout).
     */
    public function deleteByHash(string $tokenHash): void
    {
        try {
            $stmt = $this->pdo->prepare(
                'DELETE FROM remember_tokens WHERE token_hash = :token_hash',
            );
            $stmt->execute([':token_hash' => $tokenHash]);
        } catch (PDOException $e) {
            throw new DatabaseException('Failed to delete remember token', previous: $e);
        }
    }

    /**
     * Deletes all remember-me tokens for a given user (full logout / revoke all sessions).
     */
    public function deleteAllForUser(string $userId): void
    {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM remember_tokens WHERE user_id = :user_id');
            $stmt->execute([':user_id' => $userId]);
        } catch (PDOException $e) {
            throw new DatabaseException(
                "Failed to delete all remember tokens for user $userId",
                previous: $e,
            );
        }
    }
}
