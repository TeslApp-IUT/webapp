<?php

declare(strict_types=1);

namespace Teslapp\Models\Climate\ValueObjects;

/**
 * Enum representing the two possible climate actions
 * Start : activates the climate system
 * Stop : deactivates the climate system
 **/
enum ClimateAction: string
{
    case Start = 'start';
    case Stop = 'stop';
}
