<?php

declare(strict_types=1);

namespace Teslapp\Models\Shared\TeslaApi;

use Teslapp\Models\Shared\Exceptions\TeslaApiException;
use Teslapp\Models\Shared\ValueObjects\Vin;

/**
 * Climate preconditioning commands on the Tesla API.
 * The access token is handled by TeslaHttpClient, so it is not passed here.
 */
interface ClimateCommandClient
{
    /**
     * Adds or updates a preconditioning schedule (Tesla `add_precondition_schedule`).
     *
     * @param int $preconditionTimeMinutes minutes after midnight (1:05 AM = 65)
     * @param string $daysOfWeekCsv e.g. "Monday,Thursday" (also "All", "Weekdays")
     * @param bool $oneTime true = runs once then disables, false = recurring
     * @param int|null $scheduleId Tesla schedule id to modify, null to create
     * @return int|null the Tesla schedule id, null in dry-run or if the response carries none
     * @throws TeslaApiException
     */
    public function addPreconditionSchedule(
        Vin $vin,
        int $preconditionTimeMinutes,
        string $daysOfWeekCsv,
        bool $enabled,
        bool $oneTime,
        float $lat,
        float $lon,
        ?int $scheduleId = null,
    ): ?int;

    /**
     * Removes a preconditioning schedule (Tesla `remove_precondition_schedule`).
     *
     * @throws TeslaApiException
     */
    public function removePreconditionSchedule(Vin $vin, int $scheduleId): void;
}
