<?php

declare(strict_types=1);

namespace Teslapp\Models\Shared\TeslaApi;

use Teslapp\Models\Shared\Exceptions\VehicleAsleepException;
use Teslapp\Models\Shared\ValueObjects\Vin;

/**
 * Wake-on-demand policy shared by every command service (vehicle, charging,
 * climate, schedules). Runs a Tesla command and, when the vehicle is reported
 * asleep (HTTP 408 -> VehicleAsleepException), wakes it up once and retries the
 * command while it comes online — the command itself is the readiness probe,
 * since the wake_up response returns before the vehicle is actually awake.
 *
 * A single wake_up per call plus a bounded number of retries rules out wake
 * loops; callers never need to wake the vehicle explicitly.
 */
final readonly class VehicleWaker
{
    /**
     * Seconds to wait before each retry while the vehicle wakes up. A vehicle
     * typically takes 10-20s to come online; the total (~18s of waits plus the
     * HTTP round trips) stays well under nginx's 60s fastcgi_read_timeout.
     */
    private const WAKE_RETRY_DELAYS_SECONDS = [3, 6, 9];

    /** @param list<int> $wakeRetryDelays Overridable for tests (no real sleep). */
    public function __construct(
        private VehicleCommandClient $commands,
        private array $wakeRetryDelays = self::WAKE_RETRY_DELAYS_SECONDS,
    ) {}

    /**
     * Runs the command; when Tesla reports the vehicle asleep, wakes it up and
     * retries while it comes online. Returns the command's return value (e.g.
     * the Tesla schedule id the planner services push to the car).
     *
     * @template T
     * @param callable(): T $command
     * @return T
     *
     * @throws VehicleAsleepException when the vehicle did not wake up in time.
     */
    public function runAwake(Vin $vin, callable $command): mixed
    {
        try {
            return $command();
        } catch (VehicleAsleepException) {
            $this->commands->wakeUp($vin);
        }

        $delays = $this->wakeRetryDelays !== [] ? $this->wakeRetryDelays : [0];
        $lastDelay = array_pop($delays);

        foreach ($delays as $delaySeconds) {
            sleep($delaySeconds);

            try {
                return $command();
            } catch (VehicleAsleepException) {
                // Not awake yet: keep waiting, the final attempt below decides.
            }
        }

        sleep($lastDelay);

        return $command();
    }
}
