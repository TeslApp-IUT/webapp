<?php
declare(strict_types=1);

namespace Teslapp\Models\Climate\ValueObjects;

use InvalidArgumentException;

final readonly class Temperature
{
    public float $value;

    public function __construct(float $value)
    {
        if ($value < 15.0 || $value > 28.0) {
            throw new InvalidArgumentException("Temperature $value");
        }
        $this->value = $value;
    }
}
