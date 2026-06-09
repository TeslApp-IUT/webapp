<?php

declare(strict_types=1);

namespace Teslapp\Models\Shared\TeslaApi;

use Teslapp\Models\Shared\ValueObjects\Vin;

/**
 * Read access to the latest vehicle telemetry stored in the fleet_telemetry schema.
 */
interface VehicleTelemetryRepositoryInterface
{
    /**
     * Latest value of each dashboard telemetry signal for a VIN.
     * Signals with no recorded data come back as null.
     *
     * @return array<string, mixed>
     */
    public function getLatestTelemetry(Vin $vin): array;
}
