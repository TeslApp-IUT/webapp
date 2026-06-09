<?php

declare(strict_types=1);

namespace Teslapp\Models\Charging;

use Teslapp\Models\Shared\ValueObjects\DayOfWeek;
use Teslapp\Models\Shared\ValueObjects\GeoPoint;
use Teslapp\Models\Shared\ValueObjects\Vin;

/**
 * A charging schedule: a charging window opening at `activationHour` (and optionally
 * closing at `deactivationHour`) on `days` — typically an off-peak tariff window.
 * The window may cross midnight (e.g. 23:30 → 07:30).
 * Maps to charging_planner (+ charging_plans for the days).
 */
final readonly class ChargingPlanner
{
    /**
     * @param string|null $id UUID, null before it is saved
     * @param string $activationHour window start, "HH:MM"
     * @param string|null $deactivationHour window end, "HH:MM"; null = open-ended
     * @param bool $deactivateAfterSuccess true = one-off, false = recurring (kept long term)
     * @param list<DayOfWeek> $days
     * @param bool $enabled current on/off state (a new plan is active)
     * @param GeoPoint|null $location the geofence where the charging window applies
     * @param string|null $locationLabel readable address of the location
     * @param int|null $teslaScheduleId Tesla-side schedule id, set after the push (null in dry-run)
     */
    public function __construct(
        public ?string $id,
        public Vin $vin,
        public string $activationHour,
        public ?string $deactivationHour,
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

    /** Window start in minutes after midnight, for Tesla's `start_time` parameter. */
    public function startTimeMinutes(): int
    {
        return self::toMinutes($this->activationHour);
    }

    /**
     * Window end in minutes after midnight, for Tesla's `end_time` parameter.
     * May be lower than the start (window crossing midnight); null when open-ended.
     */
    public function endTimeMinutes(): ?int
    {
        return $this->deactivationHour !== null ? self::toMinutes($this->deactivationHour) : null;
    }

    /** Sorted day ids (1-7), for the charging_plans rows. */
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
     * @param array<string, mixed> $row a charging_planner row
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
            deactivationHour: isset($row['deactivation_hour'])
                ? substr((string) $row['deactivation_hour'], 0, 5)
                : null,
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

    private static function toMinutes(string $hour): int
    {
        [$hours, $minutes] = explode(':', $hour);

        return (int) $hours * 60 + (int) $minutes;
    }
}
