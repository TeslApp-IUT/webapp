<?php

declare(strict_types=1);

namespace Teslapp\Models\Shared\TeslaApi;

use Teslapp\Models\Charging\ValueObjects\ChargeLimit;
use Teslapp\Models\Charging\ValueObjects\ChargingAmps;
use Teslapp\Models\Shared\Exceptions\TeslaApiException;
use Teslapp\Models\Shared\ValueObjects\Vin;

/**
 * Charging commands on the Tesla API (immediate commands + charge schedules).
 * The access token is handled by TeslaHttpClient, so it is not passed here.
 */
interface ChargingCommandClient
{
    /**
     * Starts charging (Tesla `charge_start`).
     *
     * @throws TeslaApiException
     */
    public function startCharging(Vin $vin): void;

    /**
     * Stops charging (Tesla `charge_stop`).
     *
     * @throws TeslaApiException
     */
    public function stopCharging(Vin $vin): void;

    /**
     * Sets the battery charge limit (Tesla `set_charge_limit`).
     *
     * @throws TeslaApiException
     */
    public function setChargeLimit(Vin $vin, ChargeLimit $limit): void;

    /**
     * Sets the requested charging current (Tesla `set_charging_amps`).
     *
     * @throws TeslaApiException
     */
    public function setChargingAmps(Vin $vin, ChargingAmps $amps): void;

    /**
     * Adds or updates a charge schedule (Tesla `add_charge_schedule`).
     *
     * The schedule is a charging window: while parked at the given location, the car
     * only charges between start and end — typically an off-peak tariff window.
     * The window may cross midnight (e.g. 23:30 → 07:30).
     *
     * @param int $startTimeMinutes window start, minutes after midnight (23:30 = 1410)
     * @param int|null $endTimeMinutes window end, minutes after midnight; null = open-ended
     * @param string $daysOfWeekCsv e.g. "Monday,Thursday" (also "All", "Weekdays")
     * @param bool $oneTime true = runs once then disables, false = recurring
     * @param string|null $name shown in the vehicle UI (e.g. the location label)
     * @param int|null $scheduleId Tesla schedule id to modify, null to create
     * @return int|null the Tesla schedule id, null in dry-run or if the response carries none
     * @throws TeslaApiException
     */
    public function addChargeSchedule(
        Vin $vin,
        int $startTimeMinutes,
        ?int $endTimeMinutes,
        string $daysOfWeekCsv,
        bool $enabled,
        bool $oneTime,
        float $lat,
        float $lon,
        ?string $name = null,
        ?int $scheduleId = null,
    ): ?int;

    /**
     * Removes a charge schedule (Tesla `remove_charge_schedule`).
     *
     * @throws TeslaApiException
     */
    public function removeChargeSchedule(Vin $vin, int $scheduleId): void;
}
