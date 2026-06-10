<?php

declare(strict_types=1);

namespace Teslapp\Models\Vehicle;

use Teslapp\Models\Shared\ValueObjects\Vin;

interface VehicleRepositoryInterface
{
    public function findByVin(Vin $vin): ?Vehicle;

    public function findByPublicId(string $publicId): ?Vehicle;

    /** @return Vehicle[] */
    public function findByUser(string $userId): array;

    public function save(Vehicle $vehicle): void;

    public function detachByVin(Vin $vin): void;

    public function isAccessibleBy(Vin $vin, string $userId): bool;
}
