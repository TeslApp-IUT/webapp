<?php
declare(strict_types=1);

namespace Teslapp\Models\Climate\ValueObjects;

use InvalidArgumentException;

/**
 * Value object representing a valid cabin temperature
 * Range: 15°C to 28°C
 **/
final readonly class Temperature
{
    public float $value;

    /**
     * @throws InvalidArgumentException if the temperature is outside the accepted range
     **/
    public function __construct(float $value)
    {
        if ($value < 15.0 || $value > 28.0) {
            throw new InvalidArgumentException("Temperature $value is out of range [15, 28].");
        }
        $this->value = $value;
    }
}
