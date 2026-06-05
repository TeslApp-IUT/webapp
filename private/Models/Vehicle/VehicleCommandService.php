<?php

declare(strict_types=1);

namespace Teslapp\Models\Vehicle;

use Teslapp\Models\Shared\Exceptions\VehicleUnauthorizedException;
use Teslapp\Models\Shared\TeslaApi\VehicleCommandClient;
use Teslapp\Models\Shared\ValueObjects\AccessToken;
use Teslapp\Models\Shared\ValueObjects\TrunkSide;
use Teslapp\Models\Shared\ValueObjects\Vin;

/**
 * Vehicle command use cases. Every command first checks the user owns the vehicle
 * (authorization by VIN, throwing VehicleUnauthorizedException otherwise), then
 * delegates to the Tesla command port.
 */
final class VehicleCommandService
{
    public function __construct(
        private readonly VehicleCommandClient $commands,
        private readonly VehicleRepositoryInterface $vehicles,
    ) {}

    /** Locks the vehicle's doors. */
    public function lock(string $userId, Vin $vin, AccessToken $token): void
    {
        $this->assertAccessibleBy($userId, $vin);
        $this->commands->lock($token, $vin);
    }

    /** Unlocks the vehicle's doors. */
    public function unlock(string $userId, Vin $vin, AccessToken $token): void
    {
        $this->assertAccessibleBy($userId, $vin);
        $this->commands->unlock($token, $vin);
    }

    /** Honks the horn. */
    public function honkHorn(string $userId, Vin $vin, AccessToken $token): void
    {
        $this->assertAccessibleBy($userId, $vin);
        $this->commands->honkHorn($token, $vin);
    }

    /** Briefly flashes the headlights. */
    public function flashLights(string $userId, Vin $vin, AccessToken $token): void
    {
        $this->assertAccessibleBy($userId, $vin);
        $this->commands->flashLights($token, $vin);
    }

    /** Opens or closes the front or rear trunk. */
    public function actuateTrunk(string $userId, Vin $vin, TrunkSide $side, AccessToken $token): void
    {
        $this->assertAccessibleBy($userId, $vin);
        $this->commands->actuateTrunk($token, $vin, $side);
    }

    /** Opens the charge port door. */
    public function openChargePortDoor(string $userId, Vin $vin, AccessToken $token): void
    {
        $this->assertAccessibleBy($userId, $vin);
        $this->commands->openChargePortDoor($token, $vin);
    }

    /** Closes the charge port door. */
    public function closeChargePortDoor(string $userId, Vin $vin, AccessToken $token): void
    {
        $this->assertAccessibleBy($userId, $vin);
        $this->commands->closeChargePortDoor($token, $vin);
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
