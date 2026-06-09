<?php

declare(strict_types=1);

namespace Teslapp\Models\Shared\ValueObjects;

/**
 * Day of the week. The id matches the day_of_week table (1 = Monday ... 7 = Sunday).
 */
enum DayOfWeek: int
{
    case Monday = 1;
    case Tuesday = 2;
    case Wednesday = 3;
    case Thursday = 4;
    case Friday = 5;
    case Saturday = 6;
    case Sunday = 7;

    /** French label shown in the UI. */
    public function labelFr(): string
    {
        return match ($this) {
            self::Monday => 'Lundi',
            self::Tuesday => 'Mardi',
            self::Wednesday => 'Mercredi',
            self::Thursday => 'Jeudi',
            self::Friday => 'Vendredi',
            self::Saturday => 'Samedi',
            self::Sunday => 'Dimanche',
        };
    }

    /** Day name used by Tesla's days_of_week parameter (e.g. "Monday"). */
    public function teslaName(): string
    {
        return $this->name;
    }
}
