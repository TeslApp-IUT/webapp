<?php
declare(strict_types=1);

namespace Teslapp\Models\Climate\ValueObjects;

enum KeeperMode: int
{
    case Off = 0;
    case Keep = 1;
    case Dog = 2;
    case Camp = 3;
}
