<?php
declare(strict_types=1);

namespace Teslapp\Models\Charging\ValueObjects;

use InvalidArgumentException;

/**
 * Charging current request, in amperes.
 *
 * The actual upper bound depends on the vehicle and the connected equipment
 * (charge_state.charge_current_request_max); the firmware clamps higher values.
 */
final readonly class ChargingAmps
{
    public int $value;

    public function __construct(int $value)
    {
        if ($value < 5 || $value > 48) {
            throw new InvalidArgumentException("Charging amps $value");
        }
        $this->value = $value;
    }
}
