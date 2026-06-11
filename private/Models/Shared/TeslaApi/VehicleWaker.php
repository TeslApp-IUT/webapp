<?php

declare(strict_types=1);

namespace Teslapp\Models\Shared\TeslaApi;

use Teslapp\Models\Shared\Exceptions\TeslaApiException;
use Teslapp\Models\Shared\Exceptions\VehicleAsleepException;
use Teslapp\Models\Shared\ValueObjects\VehicleConnectivityStatus;
use Teslapp\Models\Shared\ValueObjects\Vin;

/**
 * Wake-on-demand policy shared by every command service (vehicle, charging,
 * climate, schedules). Runs a Tesla command and, when the vehicle turns out to
 * be asleep, wakes it up once and retries the command while it comes online —
 * the command itself is the readiness probe, since the wake_up response
 * returns before the vehicle is actually awake.
 *
 * A sleeping vehicle is detected two ways: the Fleet API's own 408
 * (VehicleAsleepException), or — because the signing proxy does not always
 * surface a clean 408 and may time out or answer 5xx while failing its
 * handshake against a sleeping vehicle — any other command error followed by
 * a connectivity check reporting the vehicle asleep. A single wake_up per
 * call plus a bounded number of retries rules out wake loops.
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
        private VehicleStateClient $state,
        private array $wakeRetryDelays = self::WAKE_RETRY_DELAYS_SECONDS,
    ) {}

    /**
     * Runs the command; when the vehicle is asleep, wakes it up and retries
     * while it comes online. Returns the command's return value (e.g. the
     * Tesla schedule id the planner services push to the car).
     *
     * @template T
     * @param callable(): T $command
     * @return T
     *
     * @throws VehicleAsleepException when the vehicle did not wake up in time.
     * @throws TeslaApiException when the command fails for another reason.
     */
    public function runAwake(Vin $vin, callable $command): mixed
    {
        try {
            return $command();
        } catch (VehicleAsleepException) {
            // Fleet API answered 408: the vehicle is asleep for sure.
        } catch (TeslaApiException $e) {
            if (!$this->isAsleep($vin)) {
                throw $e;
            }
            error_log(
                "Wake-on-demand: command failed ({$e->getMessage()}) and " .
                    "$vin->value reports asleep — waking it up.",
            );
        }

        $this->commands->wakeUp($vin);

        $delays = $this->wakeRetryDelays !== [] ? $this->wakeRetryDelays : [0];
        $lastDelay = array_pop($delays);

        foreach ($delays as $delaySeconds) {
            sleep($delaySeconds);

            try {
                return $command();
            } catch (TeslaApiException) {
                // Not awake yet (408, or proxy error while the vehicle boots):
                // keep waiting, the final attempt below decides.
            }
        }

        sleep($lastDelay);

        return $command();
    }

    /**
     * Asks the Fleet API for the vehicle's live connectivity state. This GET
     * never reaches the vehicle itself (Tesla answers from its own backend),
     * so it stays reliable while the vehicle sleeps. On Offline/Unknown a
     * wake_up would not help, so only Asleep counts.
     */
    private function isAsleep(Vin $vin): bool
    {
        try {
            $statuses = $this->state->fetchConnectivity();
        } catch (TeslaApiException) {
            // Cannot tell: let the original command error surface.
            return false;
        }

        return ($statuses[$vin->value] ?? null) === VehicleConnectivityStatus::Asleep;
    }
}
