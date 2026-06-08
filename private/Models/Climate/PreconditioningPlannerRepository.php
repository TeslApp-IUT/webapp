<?php

declare(strict_types=1);

namespace Teslapp\Models\Climate;

use PDO;
use PDOException;
use Teslapp\Models\DatabaseException;
use Teslapp\Models\Shared\ValueObjects\Vin;

/**
 * Persists preconditioning schedules and their days
 * (preconditioning_planner + preconditioning_plans).
 */
final readonly class PreconditioningPlannerRepository implements PreconditioningPlannerRepositoryInterface
{
    public function __construct(private PDO $pdo) {}

    public function findByVin(Vin $vin): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, vin, activation_hour, deactivate_after_success, enabled,
                    activation_latitude, activation_longitude, location_label
             FROM preconditioning_planner
             WHERE vin = :vin
             ORDER BY activation_hour',
        );
        $stmt->execute([':vin' => $vin->value]);

        return array_values(array_map(
            fn(array $row): PreconditioningPlanner => PreconditioningPlanner::fromRow(
                $row,
                $this->dayIdsFor((string) $row['id']),
            ),
            $stmt->fetchAll(),
        ));
    }

    public function findById(string $id): ?PreconditioningPlanner
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, vin, activation_hour, deactivate_after_success, enabled,
                    activation_latitude, activation_longitude, location_label
             FROM preconditioning_planner
             WHERE id = :id',
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? PreconditioningPlanner::fromRow($row, $this->dayIdsFor($id)) : null;
    }

    public function save(PreconditioningPlanner $planner): string
    {
        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO preconditioning_planner
                     (id, vin, activation_hour, deactivate_after_success, enabled,
                      activation_latitude, activation_longitude, location_label)
                 VALUES (gen_random_uuid(), :vin, :hour, :deactivate, :enabled, :lat, :lon, :label)
                 RETURNING id',
            );
            $params = $this->writableColumns($planner);
            $params[':enabled'] = $planner->enabled ? 'true' : 'false';
            $stmt->execute($params);
            $id = (string) $stmt->fetchColumn();

            $this->insertDays($id, $planner->dayIds());

            $this->pdo->commit();

            return $id;
        } catch (PDOException $e) {
            $this->pdo->rollBack();

            throw new DatabaseException(
                "Failed to save preconditioning planner for VIN {$planner->vin->value}",
                previous: $e,
            );
        }
    }

    public function update(PreconditioningPlanner $planner): void
    {
        $id = $planner->id
            ?? throw new DatabaseException('Cannot update a preconditioning planner without an id');

        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare(
                'UPDATE preconditioning_planner
                 SET activation_hour = :hour,
                     deactivate_after_success = :deactivate,
                     activation_latitude = :lat,
                     activation_longitude = :lon,
                     location_label = :label
                 WHERE id = :id',
            );
            $stmt->execute([
                ':hour' => $planner->activationHour,
                ':deactivate' => $planner->deactivateAfterSuccess ? 'true' : 'false',
                ':lat' => $planner->location?->latitude,
                ':lon' => $planner->location?->longitude,
                ':label' => $planner->locationLabel,
                ':id' => $id,
            ]);

            $this->pdo->prepare('DELETE FROM preconditioning_plans WHERE id = :id')
                ->execute([':id' => $id]);
            $this->insertDays($id, $planner->dayIds());

            $this->pdo->commit();
        } catch (PDOException $e) {
            $this->pdo->rollBack();

            throw new DatabaseException("Failed to update preconditioning planner {$id}", previous: $e);
        }
    }

    public function deleteById(string $id): void
    {
        try {
            // preconditioning_plans rows are removed by the FK cascade.
            $this->pdo->prepare('DELETE FROM preconditioning_planner WHERE id = :id')
                ->execute([':id' => $id]);
        } catch (PDOException $e) {
            throw new DatabaseException("Failed to delete preconditioning planner {$id}", previous: $e);
        }
    }

    /** @return array<string, string|float|null> */
    private function writableColumns(PreconditioningPlanner $planner): array
    {
        return [
            ':vin' => $planner->vin->value,
            ':hour' => $planner->activationHour,
            ':deactivate' => $planner->deactivateAfterSuccess ? 'true' : 'false',
            ':lat' => $planner->location?->latitude,
            ':lon' => $planner->location?->longitude,
            ':label' => $planner->locationLabel,
        ];
    }

    /** @param list<int> $dayIds */
    private function insertDays(string $id, array $dayIds): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO preconditioning_plans (id, day_id) VALUES (:id, :day_id)');
        foreach ($dayIds as $dayId) {
            $stmt->execute([':id' => $id, ':day_id' => $dayId]);
        }
    }

    /** @return list<int> */
    private function dayIdsFor(string $id): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT day_id FROM preconditioning_plans WHERE id = :id ORDER BY day_id',
        );
        $stmt->execute([':id' => $id]);

        $dayIds = [];
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $value) {
            $dayIds[] = (int) $value;
        }

        return $dayIds;
    }
}
