<?php

declare(strict_types=1);

namespace Teslapp\Models\Shared\TeslaApi;

use Teslapp\Models\Shared\ValueObjects\AccessToken;
use Teslapp\Models\Shared\ValueObjects\Vin;

/**
 * Tesla Fleet API adapter for vehicle commands.
 *
 * NOTE: signed commands (lock, unlock, trunk, charge port…) must ultimately be
 * sent through the tesla-http-proxy, which signs them with the application's
 * virtual key. This adapter targets the Fleet API directly via TeslaHttpClient;
 * pointing it at the proxy is a deployment concern handled when testing against a
 * real vehicle. Unit tests mock the VehicleCommandClient port, so this is not blocking.
 */
final readonly class TeslaCommandClient implements VehicleCommandClient
{
    public function __construct() {}

    public function lock(AccessToken $token, Vin $vin): void
    {
        TeslaHttpClient::post("/api/1/vehicles/{$vin->value}/command/door_lock", $token);
    }

    public function unlock(AccessToken $token, Vin $vin): void
    {
        TeslaHttpClient::post("/api/1/vehicles/{$vin->value}/command/door_unlock", $token);
    }

    public function honkHorn(AccessToken $token, Vin $vin): void
    {
        TeslaHttpClient::post("/api/1/vehicles/{$vin->value}/command/honk_horn", $token);
    }

    public function flashLights(AccessToken $token, Vin $vin): void
    {
        TeslaHttpClient::post("/api/1/vehicles/{$vin->value}/command/flash_lights", $token);
    }
}
