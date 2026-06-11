<?php

declare(strict_types=1);

namespace Teslapp\Models\Vehicle;

use Teslapp\Models\Shared\Exceptions\VehicleUnauthorizedException;
use Teslapp\Models\Shared\TeslaApi\VehicleCommandClient;
use Teslapp\Models\Shared\TeslaApi\VehicleWaker;
use Teslapp\Models\Shared\ValueObjects\TrunkSide;
use Teslapp\Models\Shared\ValueObjects\Vin;

/**
 * Vehicle command use cases. Every command first checks the user owns the vehicle
 * (authorization by VIN, throwing VehicleUnauthorizedException otherwise), then
 * delegates to the Tesla command port. The access token is resolved centrally by
 * TeslaHttpClient (from the session), so it is not threaded through here.
 *
 * Wake on demand: every command runs through the shared VehicleWaker, which
 * wakes a sleeping vehicle and retries, so callers never need to wake it
 * explicitly.
 */
final readonly class VehicleCommandService
{
    public function __construct(
        private VehicleCommandClient $commands,
        private VehicleRepositoryInterface $vehicles,
        private VehicleWaker $waker,
    ) {}

    /** Locks the vehicle's doors. */
    public function lock(string $userId, Vin $vin): void
    {
        $this->assertAccessibleBy($userId, $vin);
        $this->waker->runAwake($vin, fn() => $this->commands->lock($vin));
    }

    /** Unlocks the vehicle's doors. */
    public function unlock(string $userId, Vin $vin): void
    {
        $this->assertAccessibleBy($userId, $vin);
        $this->waker->runAwake($vin, fn() => $this->commands->unlock($vin));
    }

    /** Honks the horn. */
    public function honkHorn(string $userId, Vin $vin): void
    {
        $this->assertAccessibleBy($userId, $vin);
        $this->waker->runAwake($vin, fn() => $this->commands->honkHorn($vin));
    }

    /** Briefly flashes the headlights. */
    public function flashLights(string $userId, Vin $vin): void
    {
        $this->assertAccessibleBy($userId, $vin);
        $this->waker->runAwake($vin, fn() => $this->commands->flashLights($vin));
    }

    /** Opens or closes the front or rear trunk. */
    public function actuateTrunk(string $userId, Vin $vin, TrunkSide $side): void
    {
        $this->assertAccessibleBy($userId, $vin);
        $this->waker->runAwake($vin, fn() => $this->commands->actuateTrunk($vin, $side));
    }

    /** Opens the charge port door. */
    public function openChargePortDoor(string $userId, Vin $vin): void
    {
        $this->assertAccessibleBy($userId, $vin);
        $this->waker->runAwake($vin, fn() => $this->commands->openChargePortDoor($vin));
    }

    /** Closes the charge port door. */
    public function closeChargePortDoor(string $userId, Vin $vin): void
    {
        $this->assertAccessibleBy($userId, $vin);
        $this->waker->runAwake($vin, fn() => $this->commands->closeChargePortDoor($vin));
    }

    /** Wakes the vehicle from sleep. */
    public function wakeUp(string $userId, Vin $vin): void
    {
        $this->assertAccessibleBy($userId, $vin);
        $this->commands->wakeUp($vin);
    }

    /**
     * @throws VehicleUnauthorizedException if the user does not own the vehicle.
     */
    private function assertAccessibleBy(string $userId, Vin $vin): void
    {
        if (!$this->vehicles->isAccessibleBy($vin, $userId)) {
            throw new VehicleUnauthorizedException($vin->value, $userId);
        }
    }
}
