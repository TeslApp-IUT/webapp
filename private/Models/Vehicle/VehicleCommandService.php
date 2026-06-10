<?php

declare(strict_types=1);

namespace Teslapp\Models\Vehicle;

use Teslapp\Models\Shared\Exceptions\VehicleAsleepException;
use Teslapp\Models\Shared\Exceptions\VehicleUnauthorizedException;
use Teslapp\Models\Shared\TeslaApi\VehicleCommandClient;
use Teslapp\Models\Shared\ValueObjects\TrunkSide;
use Teslapp\Models\Shared\ValueObjects\Vin;

/**
 * Vehicle command use cases. Every command first checks the user owns the vehicle
 * (authorization by VIN, throwing VehicleUnauthorizedException otherwise), then
 * delegates to the Tesla command port. The access token is resolved centrally by
 * TeslaHttpClient (from the session), so it is not threaded through here.
 *
 * Wake on demand: when Tesla reports the vehicle asleep (VehicleAsleepException),
 * the command wakes it up and retries while it comes online, so callers never
 * need to wake the vehicle explicitly.
 */
final class VehicleCommandService
{
    /**
     * Seconds to wait before each retry while the vehicle wakes up. A vehicle
     * typically takes 10-20s to come online; the total stays under the 30s
     * PHP-FPM execution budget.
     */
    private const WAKE_RETRY_DELAYS_SECONDS = [3, 6, 9];

    /** @param list<int> $wakeRetryDelays Overridable for tests (no real sleep). */
    public function __construct(
        private readonly VehicleCommandClient $commands,
        private readonly VehicleRepositoryInterface $vehicles,
        private readonly array $wakeRetryDelays = self::WAKE_RETRY_DELAYS_SECONDS,
    ) {}

    /** Locks the vehicle's doors. */
    public function lock(string $userId, Vin $vin): void
    {
        $this->assertAccessibleBy($userId, $vin);
        $this->runAwake($vin, fn() => $this->commands->lock($vin));
    }

    /** Unlocks the vehicle's doors. */
    public function unlock(string $userId, Vin $vin): void
    {
        $this->assertAccessibleBy($userId, $vin);
        $this->runAwake($vin, fn() => $this->commands->unlock($vin));
    }

    /** Honks the horn. */
    public function honkHorn(string $userId, Vin $vin): void
    {
        $this->assertAccessibleBy($userId, $vin);
        $this->runAwake($vin, fn() => $this->commands->honkHorn($vin));
    }

    /** Briefly flashes the headlights. */
    public function flashLights(string $userId, Vin $vin): void
    {
        $this->assertAccessibleBy($userId, $vin);
        $this->runAwake($vin, fn() => $this->commands->flashLights($vin));
    }

    /** Opens or closes the front or rear trunk. */
    public function actuateTrunk(string $userId, Vin $vin, TrunkSide $side): void
    {
        $this->assertAccessibleBy($userId, $vin);
        $this->runAwake($vin, fn() => $this->commands->actuateTrunk($vin, $side));
    }

    /** Opens the charge port door. */
    public function openChargePortDoor(string $userId, Vin $vin): void
    {
        $this->assertAccessibleBy($userId, $vin);
        $this->runAwake($vin, fn() => $this->commands->openChargePortDoor($vin));
    }

    /** Closes the charge port door. */
    public function closeChargePortDoor(string $userId, Vin $vin): void
    {
        $this->assertAccessibleBy($userId, $vin);
        $this->runAwake($vin, fn() => $this->commands->closeChargePortDoor($vin));
    }

    /** Wakes the vehicle from sleep. */
    public function wakeUp(string $userId, Vin $vin): void
    {
        $this->assertAccessibleBy($userId, $vin);
        $this->commands->wakeUp($vin);
    }

    /**
     * Runs a command; when Tesla reports the vehicle asleep, wakes it up and
     * retries while it comes online.
     *
     * @param callable(): void $command
     *
     * @throws VehicleAsleepException when the vehicle did not wake up in time.
     */
    private function runAwake(Vin $vin, callable $command): void
    {
        try {
            $command();

            return;
        } catch (VehicleAsleepException) {
            $this->commands->wakeUp($vin);
        }

        $delays = $this->wakeRetryDelays !== [] ? $this->wakeRetryDelays : [0];
        $lastAttempt = count($delays) - 1;

        foreach ($delays as $attempt => $delaySeconds) {
            sleep($delaySeconds);

            try {
                $command();

                return;
            } catch (VehicleAsleepException $e) {
                if ($attempt === $lastAttempt) {
                    throw $e;
                }
            }
        }
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
