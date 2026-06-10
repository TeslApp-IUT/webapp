<?php

declare(strict_types=1);

namespace Teslapp\Models\Navigation;

use Teslapp\Models\Shared\ValueObjects\GeoPoint;
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

    /**
     * Fetches a single trip by its id, scoped to the given vehicle so a user
     * cannot read another vehicle's trips by guessing ids.
     *
     * @param Vin $vin owning vehicle
     * @param string $tripId trip uuid
     * @return Trip|null the trip, or null when it does not exist for this vehicle
     */
    public function findTrip(Vin $vin, string $tripId): ?Trip;

    /**
     * Loads the ordered GPS points that make up a trip's route, taken from the
     * raw telemetry positions recorded between the trip's start and end times.
     *
     * @param Trip $trip the trip whose route points to load
     * @return list<GeoPoint> route points in chronological order
     */
    public function listTripPoints(Trip $trip): array;
}
