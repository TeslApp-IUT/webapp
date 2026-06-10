<?php
declare(strict_types=1);

namespace Teslapp\Models\Shared\TeslaApi;

use Teslapp\Models\Climate\ValueObjects\KeeperMode;
use Teslapp\Models\Climate\ValueObjects\Temperature;
use Teslapp\Models\Shared\ValueObjects\Vin;

final readonly class ClimateClient
{
    public function __construct(private bool $dryRun = true) {}

    public function startClimate(Vin $vin, ?Temperature $temp = null): void
    {
        $this->post("/api/1/vehicles/$vin->value");
        if ($temp !== null) {
            $this->post("/api/1/vehicles/$vin->value", [
                'driver_temp' => $temp->value,
                'passenger_temp' => $temp->value,
            ]);
        }
    }

    public function stopClimate(Vin $vin): void
    {
        $this->post("/api/1/vehicles/$vin->value");
    }

    public function setKeeperMode(Vin $vin, KeeperMode $mode): void
    {
        $this->post("/api/1/vehicles/$vin->value", [
            'climate_keeper_mode' => $mode->value,
        ]);
    }

    /** @param array<string, mixed> $body */
    private function post(string $path, array $body = []): void
    {
        if ($this->dryRun) {
            error_log("TESLA_COMMANDS_DRY_RUN active — command not sent: $path");
            return;
        }
        TeslaHttpClient::post($path, $body);
    }
}
