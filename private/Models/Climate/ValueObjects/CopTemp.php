<?php
declare(strict_types=1);

namespace Teslapp\Models\Climate\ValueObjects;

/**
 * Enum representing the Cabin Overheat Protection temperature levels
 * Low : 90°F / 30°C
 * Medium : 95°F / 35°C
 * High : 100°F / 40°C
 **/
enum CopTemp: int
{
    case Low = 0;
    case Medium = 1;
    case High = 2;
}