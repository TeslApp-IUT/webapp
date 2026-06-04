<?php

declare(strict_types=1);

namespace Teslapp\Models\Vehicle;

/**
 * A Tesla model reference (Model 3, Model Y, Model S, Model X, Cybertruck).
 */
final readonly class TeslaModel
{
    public function __construct(public string $id, public string $name) {}

    /** @param array<string, mixed> $row A row from vehicle_models. */
    public static function fromRow(array $row): self
    {
        return new self(id: (string) $row['id'], name: (string) $row['name']);
    }
}
