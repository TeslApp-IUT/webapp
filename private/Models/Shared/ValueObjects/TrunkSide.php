<?php

declare(strict_types=1);

namespace Teslapp\Models\Shared\ValueObjects;

/**
 * Which trunk to actuate, mapped to the Tesla API `which_trunk` field.
 */
enum TrunkSide: string
{
    case Front = 'front';
    case Rear = 'rear';
}
