<?php

declare(strict_types=1);

namespace Teslapp\Models\Navigation;

use Teslapp\Models\Shared\ValueObjects\Vin;

/**
 * Service for navigation-related operations.
 */
final readonly class NavigationService
{
    public function __construct(private NavigationRepositoryInterface $repository) {}

    /**
     * Retrieves navigation data for a vehicle.
     *
     * @param string $userId User identifier
     * @param Vin $vin Vehicle VIN
     * @return array Navigation data
     */
    public function getNavigationData(string $userId, Vin $vin): array
    {
        return $this->repository->getNavigationDataForVehicle($userId, $vin);
    }
}
