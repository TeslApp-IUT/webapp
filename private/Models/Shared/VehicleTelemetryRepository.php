<?php

declare(strict_types=1);

namespace Teslapp\Models\Shared;

use PDO;
use Teslapp\Models\Shared\TeslaApi\VehicleTelemetryRepositoryInterface;
use Teslapp\Models\Shared\ValueObjects\Vin;

final readonly class VehicleTelemetryRepository implements VehicleTelemetryRepositoryInterface
{
    public function __construct(private readonly PDO $pdo) {}

    /**
     * @return array<string, mixed>
     */
    public function getLatestTelemetry(Vin $vin): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT inside_temp, ac_enabled, charge_enable, battery_level,
                    scheduled_charging_start_time, climate_keeper_mode,
                    latitude, longitude, last_seen_at
             FROM app.overview
             WHERE vin = :vin',
        );
        $stmt->execute([':vin' => $vin->value]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : [];
    }

    /**
     * Most recent value of a single signal for a VIN, or null when none was recorded.
     *
     * @param string $table  fleet_telemetry table (internal constant, not user input)
     * @param string $column column to read (internal constant, not user input)
     */
    private function latest(string $table, string $column, Vin $vin): mixed
    {
        $stmt = $this->pdo->prepare(
            "SELECT {$column}
             FROM fleet_telemetry.{$table}
             WHERE vin = :vin
             ORDER BY timestamp DESC
             LIMIT 1",
        );
        $stmt->execute([':vin' => $vin->value]);
        $row = $stmt->fetch();

        return is_array($row) ? $row[$column] ?? null : null;
    }
}
