<?php

declare(strict_types=1);

namespace Teslapp\Models\Shared\TeslaApi;

use Teslapp\Models\Shared\Exceptions\TeslaApiException;
use Teslapp\Models\Shared\ValueObjects\TrunkSide;
use Teslapp\Models\Shared\ValueObjects\Vin;

/**
 * Sends commands to a vehicle through the Tesla API. The user access token is
 * resolved centrally by TeslaHttpClient (from the session), so it is not passed
 * to these methods.
 */
interface VehicleCommandClient
{
    /**
     * Locks the vehicle's doors.
     *
     * @throws TeslaApiException on a network, HTTP (>= 400), or JSON error.
     */
    public function lock(Vin $vin): void;

    /**
     * Unlocks the vehicle's doors.
     *
     * @throws TeslaApiException on a network, HTTP (>= 400), or JSON error.
     */
    public function unlock(Vin $vin): void;

    /**
     * Honks the horn. Requires the vehicle to be in park.
     *
     * @throws TeslaApiException on a network, HTTP (>= 400), or JSON error.
     */
    public function honkHorn(Vin $vin): void;

    /**
     * Briefly flashes the headlights. Requires the vehicle to be in park.
     *
     * @throws TeslaApiException on a network, HTTP (>= 400), or JSON error.
     */
    public function flashLights(Vin $vin): void;

    /**
     * Opens or closes the front or rear trunk (the motor toggles its state).
     *
     * @throws TeslaApiException on a network, HTTP (>= 400), or JSON error.
     */
    public function actuateTrunk(Vin $vin, TrunkSide $side): void;

    /**
     * Opens the charge port door.
     *
     * @throws TeslaApiException on a network, HTTP (>= 400), or JSON error.
     */
    public function openChargePortDoor(Vin $vin): void;

    /**
     * Closes the charge port door.
     *
     * @throws TeslaApiException on a network, HTTP (>= 400), or JSON error.
     */
    public function closeChargePortDoor(Vin $vin): void;

    /**
     * Wakes the vehicle from sleep. Unlike the other commands, this hits a
     * vehicle endpoint (no /command/ segment) and is not signed.
     *
     * @throws TeslaApiException on a network, HTTP (>= 400), or JSON error.
     */
    public function wakeUp(Vin $vin): void;
}
