<?php
declare(strict_types=1);

namespace Teslapp\Models\Charging\ValueObjects;

use InvalidArgumentException;

/**
 * Target state of charge for the battery, in percent.
 *
 * Tesla only accepts limits between 50% and 100% (charge_state.charge_limit_soc_min/max).
 */
final readonly class ChargeLimit
{
    public int $value;

    public function __construct(int $value)
    {
        if ($value < 50 || $value > 100) {
            throw new InvalidArgumentException("Charge limit {$value} is out of range [50, 100].");
        }
        $this->value = $value;
    }
}
