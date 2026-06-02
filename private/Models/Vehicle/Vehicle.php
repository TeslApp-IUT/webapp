<?php

declare(strict_types=1);

namespace Teslapp\Models\Vehicle;

use Teslapp\Models\Shared\ValueObjects\Vin;

/**
 * A user's vehicle, identified by its VIN.
 */
final readonly class Vehicle
{
    public function __construct(
        public Vin $vin,
        public string $userId,
        public string $name,
        public string $modelId,
    ) {
    }

    public function isAccessibleBy(string $userId): bool
    {
        return $this->userId === $userId;
    }

    /** @param array<string, mixed> $row A row from app.vehicles. */
    public static function fromRow(array $row): self
    {
        return new self(
            vin: new Vin((string) $row['vin']),
            userId: (string) $row['user_id'],
            name: (string) $row['name'],
            modelId: (string) $row['model_id'],
        );
    }

    /**
     * Builds from a Tesla API vehicle. userId and modelId stay empty until the
     * sync fills them in before saving.
     *
     * @param array<string, mixed> $data
     */
    public static function fromTeslaResponse(array $data): self
    {
        return new self(
            vin: new Vin((string) ($data['vin'] ?? '')),
            userId: '',
            name: (string) ($data['display_name'] ?? ''),
            modelId: '',
        );
    }
}
