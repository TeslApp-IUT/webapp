<?php

declare(strict_types=1);

namespace Teslapp\Models\Charging;

use PDO;
use PDOException;
use Teslapp\Models\DatabaseException;
use Teslapp\Models\Shared\ValueObjects\Vin;

/**
 * Persists charging schedules and their days (charging_planner + charging_plans).
 */
final readonly class ChargingPlannerRepository implements ChargingPlannerRepositoryInterface
{
    public function __construct(private PDO $pdo) {}

    public function findByVin(Vin $vin): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, vin, activation_hour, deactivation_hour, deactivate_after_success,
                    enabled, activation_latitude, activation_longitude, location_label,
                    tesla_schedule_id
             FROM charging_planner
             WHERE vin = :vin
             ORDER BY activation_hour',
        );
        $stmt->execute([':vin' => $vin->value]);

        return array_values(
            array_map(
                fn(array $row): ChargingPlanner => ChargingPlanner::fromRow(
                    $row,
                    $this->dayIdsFor((string) $row['id']),
                ),
                $stmt->fetchAll(),
            ),
        );
    }

    public function findById(string $id): ?ChargingPlanner
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, vin, activation_hour, deactivation_hour, deactivate_after_success,
                    enabled, activation_latitude, activation_longitude, location_label,
                    tesla_schedule_id
             FROM charging_planner
             WHERE id = :id',
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? ChargingPlanner::fromRow($row, $this->dayIdsFor($id)) : null;
    }

    public function save(ChargingPlanner $planner): string
    {
        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO charging_planner
                     (id, vin, activation_hour, deactivation_hour, deactivate_after_success,
                      enabled, activation_latitude, activation_longitude, location_label)
                 VALUES (gen_random_uuid(), :vin, :start, :end, :deactivate, :enabled,
                         :lat, :lon, :label)
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
                "Failed to save charging planner for VIN $planner->vin->value",
                previous: $e,
            );
        }
    }

    public function update(ChargingPlanner $planner): void
    {
        $id =
            $planner->id ??
            throw new DatabaseException('Cannot update a charging planner without an id');

        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare(
                'UPDATE charging_planner
                 SET activation_hour = :start,
                     deactivation_hour = :end,
                     deactivate_after_success = :deactivate,
                     enabled = :enabled,
                     activation_latitude = :lat,
                     activation_longitude = :lon,
                     location_label = :label
                 WHERE id = :id',
            );
            $stmt->execute([
                ':start' => $planner->activationHour,
                ':end' => $planner->deactivationHour,
                ':deactivate' => $planner->deactivateAfterSuccess ? 'true' : 'false',
                ':enabled' => $planner->enabled ? 'true' : 'false',
                ':lat' => $planner->location?->latitude,
                ':lon' => $planner->location?->longitude,
                ':label' => $planner->locationLabel,
                ':id' => $id,
            ]);

            $this->pdo
                ->prepare('DELETE FROM charging_plans WHERE id = :id')
                ->execute([':id' => $id]);
            $this->insertDays($id, $planner->dayIds());

            $this->pdo->commit();
        } catch (PDOException $e) {
            $this->pdo->rollBack();

            throw new DatabaseException("Failed to update charging planner $id", previous: $e);
        }
    }

    public function setEnabled(string $id, bool $enabled): void
    {
        try {
            $stmt = $this->pdo->prepare(
                'UPDATE charging_planner SET enabled = :enabled WHERE id = :id',
            );
            $stmt->execute([':enabled' => $enabled ? 'true' : 'false', ':id' => $id]);
        } catch (PDOException $e) {
            throw new DatabaseException("Failed to toggle charging planner $id", previous: $e);
        }
    }

    public function setTeslaScheduleId(string $id, int $teslaScheduleId): void
    {
        try {
            $stmt = $this->pdo->prepare(
                'UPDATE charging_planner SET tesla_schedule_id = :tid WHERE id = :id',
            );
            $stmt->execute([':tid' => $teslaScheduleId, ':id' => $id]);
        } catch (PDOException $e) {
            throw new DatabaseException(
                "Failed to store the Tesla schedule id on charging planner $id",
                previous: $e,
            );
        }
    }

    public function deleteById(string $id): void
    {
        try {
            // charging_plans rows are removed by the FK cascade.
            $this->pdo
                ->prepare('DELETE FROM charging_planner WHERE id = :id')
                ->execute([':id' => $id]);
        } catch (PDOException $e) {
            throw new DatabaseException("Failed to delete charging planner $id", previous: $e);
        }
    }

    /** @return array<string, string|float|null> */
    private function writableColumns(ChargingPlanner $planner): array
    {
        return [
            ':vin' => $planner->vin->value,
            ':start' => $planner->activationHour,
            ':end' => $planner->deactivationHour,
            ':deactivate' => $planner->deactivateAfterSuccess ? 'true' : 'false',
            ':lat' => $planner->location?->latitude,
            ':lon' => $planner->location?->longitude,
            ':label' => $planner->locationLabel,
        ];
    }

    /** @param list<int> $dayIds */
    private function insertDays(string $id, array $dayIds): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO charging_plans (id, day_id) VALUES (:id, :day_id)',
        );
        foreach ($dayIds as $dayId) {
            $stmt->execute([':id' => $id, ':day_id' => $dayId]);
        }
    }

    /** @return list<int> */
    private function dayIdsFor(string $id): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT day_id FROM charging_plans WHERE id = :id ORDER BY day_id',
        );
        $stmt->execute([':id' => $id]);

        $dayIds = [];
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $value) {
            $dayIds[] = (int) $value;
        }

        return $dayIds;
    }
}
