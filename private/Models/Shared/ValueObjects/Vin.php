<?php

declare(strict_types=1);

namespace Teslapp\Models\Shared\ValueObjects;

use InvalidArgumentException;

/**
 * VIN — 17-character vehicle identifier (ISO 3779, excludes I/O/Q).
 */
final readonly class Vin
{
    private const PATTERN = '/^[A-HJ-NPR-Z0-9]{17}$/';

    public string $value;

    /** @throws InvalidArgumentException on an invalid VIN. */
    public function __construct(string $value)
    {
        $normalized = strtoupper(trim($value));

        if (preg_match(self::PATTERN, $normalized) !== 1) {
            throw new InvalidArgumentException("Invalid VIN: {$value}");
        }

        $this->value = $normalized;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
