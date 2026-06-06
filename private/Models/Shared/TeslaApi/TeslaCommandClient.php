<?php

declare(strict_types=1);

namespace Teslapp\Models\Shared\TeslaApi;

use Teslapp\Models\Shared\ValueObjects\TrunkSide;
use Teslapp\Models\Shared\ValueObjects\Vin;

/**
 * Tesla Fleet API adapter for vehicle commands.
 *
 * NOTE: signed commands (lock, unlock, trunk, charge port…) must ultimately be
 * sent through the tesla-http-proxy, which signs them with the application's
 * virtual key. This adapter targets the Fleet API directly via TeslaHttpClient;
 * pointing it at the proxy is a deployment concern handled when testing against a
 * real vehicle. Unit tests mock the VehicleCommandClient port, so this is not blocking.
 *
 * The user access token is resolved by TeslaHttpClient (from the session), so it
 * is not passed to these methods.
 */
final readonly class TeslaCommandClient implements VehicleCommandClient
{
    public function __construct() {}

    public function lock(Vin $vin): void
    {
        TeslaHttpClient::post("/api/1/vehicles/{$vin->value}/command/door_lock");
    }

    public function unlock(Vin $vin): void
    {
        TeslaHttpClient::post("/api/1/vehicles/{$vin->value}/command/door_unlock");
    }

    public function honkHorn(Vin $vin): void
    {
        TeslaHttpClient::post("/api/1/vehicles/{$vin->value}/command/honk_horn");
    }

    public function flashLights(Vin $vin): void
    {
        TeslaHttpClient::post("/api/1/vehicles/{$vin->value}/command/flash_lights");
    }

    public function actuateTrunk(Vin $vin, TrunkSide $side): void
    {
        TeslaHttpClient::post("/api/1/vehicles/{$vin->value}/command/actuate_trunk", [
            'which_trunk' => $side->value,
        ]);
    }

    public function openChargePortDoor(Vin $vin): void
    {
        TeslaHttpClient::post("/api/1/vehicles/{$vin->value}/command/charge_port_door_open");
    }

    public function closeChargePortDoor(Vin $vin): void
    {
        TeslaHttpClient::post("/api/1/vehicles/{$vin->value}/command/charge_port_door_close");
    }

    public function wakeUp(Vin $vin): void
    {
        // wake_up is a vehicle endpoint, not a signed command: no /command/ segment.
        TeslaHttpClient::post("/api/1/vehicles/{$vin->value}/wake_up");
    }
}
