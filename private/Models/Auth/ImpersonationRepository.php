<?php

declare(strict_types=1);

namespace Teslapp\Models\Auth;

use PDO;
use PDOException;
use Teslapp\Models\DatabaseException;

final readonly class ImpersonationRepository
{
    public function __construct(private PDO $pdo) {}

    public function isDeveloper(string $userId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT is_developer FROM app.users WHERE id = :id',
        );
        $stmt->execute([':id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false && $row['is_developer'] === true;
    }

    /**
     * Returns all users except the given developer, ordered by email.
     *
     * @return array<int, array{id: string, email: string, first_name: string, last_name: string}>
     */
    public function listUsersExcept(string $excludeId): array
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT id, email, first_name, last_name
                 FROM app.users
                 WHERE id != :exclude
                 ORDER BY email ASC',
            );
            $stmt->execute([':exclude' => $excludeId]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            throw new DatabaseException('Failed to list users for impersonation', previous: $e);
        }
    }
}
