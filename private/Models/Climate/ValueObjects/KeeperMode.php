<?php

declare(strict_types=1);

namespace Teslapp\Models\Climate\ValueObjects;

/**
 * Enum representing the available climate keeper modes
 * Off : climate is disabled
 * Keep: maintains the set temperature indefinitely
 * Dog : keeps the cabin cool
 * Camp : keeps the cabin comfortable for sleeping
 **/
enum KeeperMode: int
{
    case Off = 0;
    case Keep = 1;
    case Dog = 2;
    case Camp = 3;
}