<?php

declare(strict_types=1);

namespace Teslapp\Models\Navigation;

use PDO;
use Teslapp\Models\Shared\ValueObjects\Vin;

/**
 * Repository for navigation-related data operations.
 */
final readonly class NavigationRepository implements NavigationRepositoryInterface
{
    public function __construct(
        private PDO $pdo
    )
    {
    }

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

        return array_map(
            static fn(array $row): Trip => Trip::fromRow($row),
            $stmt->fetchAll(),
        );
    }
}
