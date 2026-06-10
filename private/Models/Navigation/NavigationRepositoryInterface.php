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
     * Pulls the list of trips from the database
     * Shows a maximum of 10 items
     *
     * @param Vin $vin
     * @param ?int $page 1-based page number; null is treated as the first page
     * @return list<Trip> trips ordered by most recent first, without their route points
     */
    public function listTrips(Vin $vin, ?int $page): array;
}
