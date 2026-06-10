<?php

declare(strict_types=1);

namespace Teslapp\Models\Navigation;

use Teslapp\Models\Shared\ValueObjects\Vin;

/**
 * Repository interface for navigation-related operations.
 */
interface NavigationRepositoryInterface
{
    /**
     * Retrieves navigation data for a vehicle.
     *
     * @param string $userId User identifier
     * @param Vin $vin Vehicle VIN
     * @return array Navigation data
     */
    public function getNavigationDataForVehicle(string $userId, Vin $vin): array;
}
