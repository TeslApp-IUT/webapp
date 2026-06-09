<?php
declare(strict_types=1);

namespace Teslapp\Models\Climate\ValueObjects;

enum ClimateAction: string
{
    case Start = 'start';
    case Stop  = 'stop';
}