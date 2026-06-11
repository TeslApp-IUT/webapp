<?php

declare(strict_types=1);

namespace Teslapp\Models\Shared\TeslaApi;

use Teslapp\Models\Climate\ValueObjects\CopTemp;
use Teslapp\Models\Climate\ValueObjects\KeeperMode;
use Teslapp\Models\Climate\ValueObjects\Temperature;
use Teslapp\Models\Shared\ValueObjects\Vin;

/**
 * Port for the immediate climate commands (start/stop, keeper mode, Cabin
 * Overheat Protection), distinct from ClimateCommandClient which only covers
 * the preconditioning schedules. Implemented by the real ClimateClient adapter;
 * services depend on this interface so the Tesla API stays a peripheral detail
 * and unit tests can mock it.
 *
 * Every method throws TeslaApiException on a network/HTTP/JSON error, including
 * its VehicleAsleepException subclass when Tesla reports the vehicle asleep.
 */
interface ClimateControlClient
{
    /**
     * Starts the climate system, disables the seat heaters, and optionally
     * applies the requested driver/passenger temperature.
     */
    public function startClimate(Vin $vin, ?Temperature $temp = null): void;

    /** Stops the climate system. */
    public function stopClimate(Vin $vin): void;

    /** Sets the climate keeper mode (Off, Keep, Dog, Camp). */
    public function setKeeperMode(Vin $vin, KeeperMode $mode): void;

    /** Sets the Cabin Overheat Protection temperature (Low, Medium, High). */
    public function setCopTemp(Vin $vin, CopTemp $level): void;
}
