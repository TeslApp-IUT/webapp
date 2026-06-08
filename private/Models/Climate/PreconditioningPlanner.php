<?php

declare(strict_types=1);

namespace Teslapp\Models\Climate;

use Teslapp\Models\Shared\ValueObjects\DayOfWeek;
use Teslapp\Models\Shared\ValueObjects\GeoPoint;
use Teslapp\Models\Shared\ValueObjects\Vin;

/**
 * A preconditioning schedule: warm up the car at `activationHour` on `days`.
 * Maps to preconditioning_planner (+ preconditioning_plans for the days).
 */
final readonly class PreconditioningPlanner
{
    /**
     * @param string|null $id UUID, null before it is saved
     * @param string $activationHour "HH:MM"
     * @param bool $deactivateAfterSuccess true = one-off, false = recurring (kept long term)
     * @param list<DayOfWeek> $days
     * @param bool $enabled current on/off state (a new plan is active)
     * @param GeoPoint|null $location the geofence where preconditioning applies
     * @param string|null $locationLabel readable address of the location
     * @param int|null $teslaScheduleId Tesla-side schedule id, set after the push (null in dry-run)
     */
    public function __construct(
        public ?string $id,
        public Vin $vin,
        public string $activationHour,
        public bool $deactivateAfterSuccess,
        public array $days,
        public bool $enabled = true,
        public ?GeoPoint $location = null,
        public ?string $locationLabel = null,
        public ?int $teslaScheduleId = null,
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
        $latitude = $row['activation_latitude'] ?? null;
        $longitude = $row['activation_longitude'] ?? null;
        $location =
            $latitude !== null && $longitude !== null
                ? new GeoPoint((float) $latitude, (float) $longitude)
                : null;

        return new self(
            id: (string) $row['id'],
            vin: new Vin((string) $row['vin']),
            // TIME comes back as "HH:MM:SS"; keep "HH:MM".
            activationHour: substr((string) $row['activation_hour'], 0, 5),
            deactivateAfterSuccess: (bool) $row['deactivate_after_success'],
            days: array_map(static fn(int $id): DayOfWeek => DayOfWeek::from($id), $dayIds),
            enabled: (bool) $row['enabled'],
            location: $location,
            locationLabel: isset($row['location_label']) ? (string) $row['location_label'] : null,
            teslaScheduleId: isset($row['tesla_schedule_id'])
                ? (int) $row['tesla_schedule_id']
                : null,
        );
    }
}
