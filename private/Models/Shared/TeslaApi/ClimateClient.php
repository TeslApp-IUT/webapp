<?php
declare(strict_types=1);

namespace Teslapp\Models\Shared\TeslaApi;

use Teslapp\Models\Climate\ValueObjects\KeeperMode;
use Teslapp\Models\Climate\ValueObjects\Temperature;
use Teslapp\Models\Climate\ValueObjects\CopTemp;
use Teslapp\Models\Shared\ValueObjects\Vin;

/**
 * Tesla Fleet API client for climate-related commands.
 * When dryRun is true, commands are only logged and never sent to Tesla.
 **/
final readonly class ClimateClient implements ClimateControlClient
{
    public function __construct(private bool $dryRun = true) {}

    /**
     * Starts the climate system for the vehicle
     * Immediately disables all seat heaters after starting
     * Optionally sets the driver and passenger temperature
     **/
    public function startClimate(Vin $vin, ?Temperature $temp = null): void
    {
        $this->post("/api/1/vehicles/{$vin->value}/command/auto_conditioning_start");

        /**
         * Disable all seat heaters after starting the climate
         * 0: front left, 1: front right, 2: rear left, 3: rear left back, 4: rear center,
         * 5: rear right, 6: rear right back, 7: third row left, 8: third row right
         **/
        foreach (range(0, 8) as $seat) {
            $this->post("/api/1/vehicles/{$vin->value}/command/remote_seat_heater_request", [
                'seat_position' => $seat,
                'level' => 0,
            ]);
        }

        /* Apply the requested temperature */
        if ($temp !== null) {
            $this->post("/api/1/vehicles/{$vin->value}/command/set_temps", [
                'driver_temp' => $temp->value,
                'passenger_temp' => $temp->value,
            ]);
        }
    }

    /**
     * Stops the climate system for the given vehicle.
     **/
    public function stopClimate(Vin $vin): void
    {
        $this->post("/api/1/vehicles/{$vin->value}/command/auto_conditioning_stop");
    }

    /**
     * Sets the climate keeper mode for the given vehicle.
     * Modes: 0 = Off, 1 = Keep, 2 = Dog, 3 = Camp
     **/
    public function setKeeperMode(Vin $vin, KeeperMode $mode): void
    {
        $this->post("/api/1/vehicles/{$vin->value}/command/set_climate_keeper_mode", [
            'climate_keeper_mode' => $mode->value,
        ]);
    }

    /**
     * Sends a POST request to the Tesla Fleet API.
     * If dryRun is enabled, logs the command without sending it.
     *
     * @param array<string, mixed> $body
     **/
    private function post(string $path, array $body = []): void
    {
        if ($this->dryRun) {
            error_log("TESLA_COMMANDS_DRY_RUN active — command not sent: $path");
            return;
        }

        TeslaHttpClient::post($path, $body);
    }

    /**
     * Sets the Cabin Overheat Protection temperature
     * Levels: 0 = Low (30°C), 1 = Medium (35°C), 2 = High (40°C)
     **/
    public function setCopTemp(Vin $vin, CopTemp $level): void
    {
        $this->post("/api/1/vehicles/{$vin->value}/command/set_cop_temp", [
            'cop_temp' => $level->value,
        ]);
    }
}
