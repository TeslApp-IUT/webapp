<?php

declare(strict_types=1);

namespace Teslapp\Models\Vehicle;

use PDO;
use PDOException;
use Teslapp\Models\DatabaseException;
use Teslapp\Models\Shared\ValueObjects\Vin;

/**
 * Persists vehicles in the `vehicles` table.
 */
final class VehicleRepository implements VehicleRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findByVin(Vin $vin): ?Vehicle
    {
        $stmt = $this->pdo->prepare('SELECT vin, user_id, name, model_id FROM vehicles WHERE vin = :vin');
        $stmt->execute([':vin' => $vin->value]);
        $row = $stmt->fetch();

        return $row !== false ? Vehicle::fromRow($row) : null;
    }

    /** @return Vehicle[] */
    public function findByUser(string $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT vin, user_id, name, model_id FROM vehicles WHERE user_id = :user_id ORDER BY name');
        $stmt->execute([':user_id' => $userId]);

        return array_map(
            static fn (array $row): Vehicle => Vehicle::fromRow($row),
            $stmt->fetchAll(),
        );
    }

    public function save(Vehicle $vehicle): void
    {
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO vehicles (vin, user_id, name, model_id) VALUES (:vin, :user_id, :name, :model_id)'
            );
            $stmt->execute([
                ':vin' => $vehicle->vin->value,
                ':user_id' => $vehicle->userId,
                ':name' => $vehicle->name,
                ':model_id' => $vehicle->modelId,
            ]);
        } catch (PDOException $e) {
            throw new DatabaseException("Failed to save vehicle {$vehicle->vin->value}", previous: $e);
        }
    }

    public function deleteByVin(Vin $vin): void
    {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM vehicles WHERE vin = :vin');
            $stmt->execute([':vin' => $vin->value]);
        } catch (PDOException $e) {
            throw new DatabaseException("Failed to delete vehicle {$vin->value}", previous: $e);
        }
    }

    public function isAccessibleBy(Vin $vin, string $userId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM vehicles WHERE vin = :vin AND user_id = :user_id');
        $stmt->execute([':vin' => $vin->value, ':user_id' => $userId]);

        return $stmt->fetchColumn() !== false;
    }
}
