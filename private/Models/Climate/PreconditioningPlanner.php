<?php

declare(strict_types=1);

namespace Teslapp\Models\Climate;

use Teslapp\Models\Shared\ValueObjects\DayOfWeek;
use Teslapp\Models\Shared\ValueObjects\Vin;

/**
 * A preconditioning schedule: the car is warmed up for `activationHour` on the
 * selected `days` (e.g. before departure).
 *
 * Maps to preconditioning_planner (+ preconditioning_plans for the days).
 */
final readonly class PreconditioningPlanner
{
    /**
     * @param string|null $id UUID, null before it is saved
     * @param string $activationHour "HH:MM"
     * @param bool $deactivateAfterSuccess true = one-off, false = recurring (kept long term)
     * @param list<DayOfWeek> $days
     */
    public function __construct(
        public ?string $id,
        public Vin $vin,
        public string $activationHour,
        public bool $deactivateAfterSuccess,
        public array $days,
    ) {}

    /** Whether the user asked to keep this schedule indefinitely (recurring). */
    public function isMemorizedLongTerm(): bool
    {
        return !$this->deactivateAfterSuccess;
    }

    /** Minutes after midnight, for Tesla's `precondition_time` parameter. */
    public function preconditionTimeMinutes(): int
    {
        [$hours, $minutes] = array_map(intval(...), explode(':', $this->activationHour));

        return $hours * 60 + $minutes;
    }

    /** Sorted day ids (1-7), for the preconditioning_plans rows. */
    public function dayIds(): array
    {
        $ids = array_map(static fn(DayOfWeek $day): int => $day->value, $this->days);
        sort($ids);

        return $ids;
    }

    /** Tesla `days_of_week` CSV, e.g. "Monday,Thursday". */
    public function daysOfWeekCsv(): string
    {
        $names = array_map(static fn(DayOfWeek $day): string => $day->teslaName(), $this->days);

        return implode(',', $names);
    }

    /**
     * @param array<string, mixed> $row a preconditioning_planner row
     * @param list<int> $dayIds the planner's day ids
     */
    public static function fromRow(array $row, array $dayIds): self
    {
        return new self(
            id: (string) $row['id'],
            vin: new Vin((string) $row['vin']),
            // TIME comes back as "HH:MM:SS"; keep "HH:MM".
            activationHour: substr((string) $row['activation_hour'], 0, 5),
            deactivateAfterSuccess: (bool) $row['deactivate_after_success'],
            days: array_map(static fn(int $id): DayOfWeek => DayOfWeek::from($id), $dayIds),
        );
    }
}
