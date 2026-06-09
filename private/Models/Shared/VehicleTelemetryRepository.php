<?php

declare(strict_types=1);

namespace Teslapp\Models\Shared;

use PDO;
use Teslapp\Models\Shared\TeslaApi\VehicleTelemetryRepositoryInterface;
use Teslapp\Models\Shared\ValueObjects\Vin;

/**
 * Reads the latest telemetry signals for a vehicle from the fleet_telemetry schema.
 *
 * Each signal lives in its own table (one row per emission); we keep the most recent
 * row per VIN. Table and column names are internal constants — never user input.
 */
final class VehicleTelemetryRepository implements VehicleTelemetryRepositoryInterface
{
    public function __construct(private readonly PDO $pdo) {}

    /**
     * @return array<string, mixed>
     */
    public function getLatestTelemetry(Vin $vin): array
    {
        return [
            'battery_level' => $this->latest('charge_level', 'battery_level', $vin),
            'charge_enable_request' => $this->latest('charge_enable', 'charge_enable_request', $vin),
            'scheduled_charging_start_time' => $this->latest(
                'charge_scheduled',
                'scheduled_charging_start_time',
                $vin,
            ),
            'inside_temp' => $this->latest('temp_int', 'inside_temp', $vin),
            'climate_keeper_mode' => $this->latest('keeper_mode', 'climate_keeper_mode', $vin),
            'hvac_ac_enabled' => $this->latest('ac_enabled', 'hvac_ac_enabled', $vin),
        ];
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

        return is_array($row) ? ($row[$column] ?? null) : null;
    }
}
