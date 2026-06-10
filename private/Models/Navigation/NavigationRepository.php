<?php

declare(strict_types=1);

namespace Teslapp\Models\Navigation;

use PDO;
use Teslapp\Models\Shared\ValueObjects\GeoPoint;
use Teslapp\Models\Shared\ValueObjects\Vin;

/**
 * Repository for navigation-related data operations.
 */
final readonly class NavigationRepository implements NavigationRepositoryInterface
{
    public function __construct(private PDO $pdo) {}

    private const PAGE_SIZE = 10;

    /** @return list<Trip> */
    public function listTrips(Vin $vin, ?int $page): array
    {
        $offset = (max($page ?? 1, 1) - 1) * self::PAGE_SIZE;

        $stmt = $this->pdo->prepare('
            SELECT id,
                   vin,
                   start_time,
                   end_time,
                   running,
                   start_location_id,
                   end_location_id,
                   start_latitude,
                   start_longitude,
                   end_latitude,
                   end_longitude,
                   total_distance
            FROM app.trips t
            WHERE t.vin = :vin
            ORDER BY end_time DESC
            LIMIT :limit OFFSET :offset
        ');

        $stmt->bindValue(':vin', $vin->value);
        $stmt->bindValue(':limit', self::PAGE_SIZE, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(static fn(array $row): Trip => Trip::fromRow($row), $stmt->fetchAll());
    }

    public function findTrip(Vin $vin, string $tripId): ?Trip
    {
        $stmt = $this->pdo->prepare('
            SELECT id,
                   vin,
                   start_time,
                   end_time,
                   running,
                   start_location_id,
                   end_location_id,
                   start_latitude,
                   start_longitude,
                   end_latitude,
                   end_longitude,
                   total_distance
            FROM app.trips t
            WHERE t.vin = :vin AND t.id = :id
        ');

        $stmt->bindValue(':vin', $vin->value);
        $stmt->bindValue(':id', $tripId);
        $stmt->execute();

        $row = $stmt->fetch();

        return $row === false ? null : Trip::fromRow($row);
    }

    public function listTripPoints(Trip $trip): array
    {
        // The trips view is built from fleet_telemetry.location; a trip's points
        // are that vehicle's positions between its start and end times, in order.
        $stmt = $this->pdo->prepare('
            SELECT latitude, longitude
            FROM fleet_telemetry.location
            WHERE vin = :vin
              AND "timestamp" >= :start
              AND "timestamp" <= :end
            ORDER BY "timestamp", id
        ');

        $stmt->bindValue(':vin', $trip->vin->value);
        $stmt->bindValue(':start', $trip->startTime->format('Y-m-d H:i:s.u'));
        $stmt->bindValue(':end', $trip->endTime->format('Y-m-d H:i:s.u'));
        $stmt->execute();

        return array_map(
            static fn(array $row): GeoPoint => new GeoPoint(
                (float) $row['latitude'],
                (float) $row['longitude'],
            ),
            $stmt->fetchAll(),
        );
    }
}
