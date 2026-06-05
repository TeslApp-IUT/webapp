<?php

declare(strict_types=1);

namespace Teslapp\Models\Shared\TeslaApi;

use Teslapp\Models\Shared\Exceptions\TeslaApiException;
use Teslapp\Models\Shared\ValueObjects\AccessToken;
use Teslapp\Models\Shared\ValueObjects\TrunkSide;
use Teslapp\Models\Shared\ValueObjects\Vin;

/**
 * Sends commands to a vehicle through the Tesla API.
 */
interface VehicleCommandClient
{
    /**
     * Locks the vehicle's doors.
     *
     * @throws TeslaApiException on a network, HTTP (>= 400), or JSON error.
     */
    public function lock(AccessToken $token, Vin $vin): void;

    /**
     * Unlocks the vehicle's doors.
     *
     * @throws TeslaApiException on a network, HTTP (>= 400), or JSON error.
     */
    public function unlock(AccessToken $token, Vin $vin): void;

    /**
     * Honks the horn. Requires the vehicle to be in park.
     *
     * @throws TeslaApiException on a network, HTTP (>= 400), or JSON error.
     */
    public function honkHorn(AccessToken $token, Vin $vin): void;

    /**
     * Briefly flashes the headlights. Requires the vehicle to be in park.
     *
     * @throws TeslaApiException on a network, HTTP (>= 400), or JSON error.
     */
    public function flashLights(AccessToken $token, Vin $vin): void;

    /**
     * Opens or closes the front or rear trunk (the motor toggles its state).
     *
     * @throws TeslaApiException on a network, HTTP (>= 400), or JSON error.
     */
    public function actuateTrunk(AccessToken $token, Vin $vin, TrunkSide $side): void;
}
