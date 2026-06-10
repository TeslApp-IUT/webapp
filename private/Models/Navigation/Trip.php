<?php

declare(strict_types=1);

namespace Teslapp\Models\Navigation;

use DateTimeImmutable;
use DateTimeZone;
use Teslapp\Models\Shared\ValueObjects\GeoPoint;
use Teslapp\Models\Shared\ValueObjects\Vin;

/**
 * A single drive: the segment between two parking stays, as derived by the
 * `app.trips` view. The list of GPS points making up the route is loaded
 * separately (it is null until {@see Trip::withPoints()} attaches it).
 */
final readonly class Trip
{
    /**
     * @param string $id deterministic UUID of the trip (view-computed)
     * @param GeoPoint $start first recorded position of the drive
     * @param GeoPoint $end last recorded position of the drive
     * @param int|null $startLocationId `location` id of the first point
     * @param int|null $endLocationId `location` id of the last point
     * @param float $totalDistance cumulative distance in metres
     * @param bool $running whether the trip is still in progress (not yet parked)
     * @param list<GeoPoint>|null $points ordered route points, or null when not loaded
     */
    public function __construct(
        public string $id,
        public Vin $vin,
        public DateTimeImmutable $startTime,
        public DateTimeImmutable $endTime,
        public bool $running,
        public ?int $startLocationId,
        public ?int $endLocationId,
        public GeoPoint $start,
        public GeoPoint $end,
        public float $totalDistance,
        public ?array $points = null,
    ) {}

    /** @param array<string, mixed> $row a row from the `app.trips` view. */
    public static function fromRow(array $row): self
    {
        $utc = new DateTimeZone('UTC');

        return new self(
            id: (string) $row['id'],
            vin: new Vin((string) $row['vin']),
            startTime: new DateTimeImmutable((string) $row['start_time'], $utc),
            endTime: new DateTimeImmutable((string) $row['end_time'], $utc),
            running: (bool) $row['running'],
            startLocationId: isset($row['start_location_id'])
                ? (int) $row['start_location_id']
                : null,
            endLocationId: isset($row['end_location_id']) ? (int) $row['end_location_id'] : null,
            start: new GeoPoint((float) $row['start_latitude'], (float) $row['start_longitude']),
            end: new GeoPoint((float) $row['end_latitude'], (float) $row['end_longitude']),
            totalDistance: (float) $row['total_distance'],
        );
    }

    /** Whether the route points have been loaded onto this trip. */
    public function hasPoints(): bool
    {
        return $this->points !== null;
    }

    /**
     * Returns a copy of this trip carrying its ordered route points.
     *
     * @param list<GeoPoint> $points
     */
    public function withPoints(array $points): self
    {
        return new self(
            id: $this->id,
            vin: $this->vin,
            startTime: $this->startTime,
            endTime: $this->endTime,
            running: $this->running,
            startLocationId: $this->startLocationId,
            endLocationId: $this->endLocationId,
            start: $this->start,
            end: $this->end,
            totalDistance: $this->totalDistance,
            points: $points,
        );
    }
}
