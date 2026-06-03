<?php

declare(strict_types=1);

namespace Teslapp\Models\Vehicle;

use PDO;

/**
 * Reads the Tesla model reference table (vehicle_models).
 */
final class TeslaModelRepository implements TeslaModelRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return TeslaModel[] */
    public function findAll(): array
    {
        $rows = $this->pdo->query('SELECT id, name FROM vehicle_models ORDER BY name')->fetchAll();

        return array_map(
            static fn (array $row): TeslaModel => TeslaModel::fromRow($row),
            $rows,
        );
    }

    public function findById(string $id): ?TeslaModel
    {
        $stmt = $this->pdo->prepare('SELECT id, name FROM vehicle_models WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? TeslaModel::fromRow($row) : null;
    }
}
