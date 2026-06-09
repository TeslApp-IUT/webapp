<?php
declare(strict_types=1);

namespace Teslapp\Models\Charging\ValueObjects;

/**
 * Immediate charging command requested from the battery page forms.
 */
enum ChargingAction: string
{
    case Start = 'start';
    case Stop = 'stop';
}
