<?php

declare(strict_types=1);

namespace Teslapp\Models\Navigation;

use Teslapp\Models\Shared\ValueObjects\Vin;

/**
 * Repository for navigation-related data operations.
 */
final class NavigationRepository implements NavigationRepositoryInterface
{
    /**
     * Retrieves navigation data for a vehicle.
     *
     * @param string $userId User identifier
     * @param Vin $vin Vehicle VIN
     * @return array Navigation data from telemetry or Tesla API
     */
    public function getNavigationDataForVehicle(string $userId, Vin $vin): array
    {
        // TODO: Implement actual navigation data retrieval from Tesla API or telemetry
        // For now, return empty array as placeholder
        return [];
    }
}
