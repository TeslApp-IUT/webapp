<?php

declare(strict_types=1);

namespace Teslapp\Controllers;

use Teslapp\Models\Shared\Exceptions\TeslaApiException;
use Teslapp\Models\Shared\Exceptions\VehicleUnauthorizedException;
use Teslapp\Models\Shared\ValueObjects\AccessToken;
use Teslapp\Models\Shared\ValueObjects\TrunkSide;
use Teslapp\Models\Shared\ValueObjects\Vin;
use Teslapp\Models\Vehicle\VehicleCommandService;
use Teslapp\Utils\Csrf;
use Teslapp\Utils\Http;

/**
 * JSON endpoints for the vehicle commands (issue #26): lock/unlock, horn,
 * headlight flash, front/rear trunk, charge port door and wake up.
 *
 * Each endpoint is a POST AJAX call answering JSON. The shared guards (HTTP
 * method, authenticated session, CSRF header, selected vehicle) and the
 * exception-to-status mapping live in run(). Kept separate from VehicleController
 * (which handles read/selection) on purpose — one controller per concern.
 */
final class VehicleCommandController
{
    public function __construct(private readonly VehicleCommandService $service) {}

    public function lock(): never
    {
        $this->run(function (string $userId, Vin $vin, AccessToken $token): void {
            $this->service->lock($userId, $vin, $token);
        });
    }

    public function unlock(): never
    {
        $this->run(function (string $userId, Vin $vin, AccessToken $token): void {
            $this->service->unlock($userId, $vin, $token);
        });
    }

    public function honk(): never
    {
        $this->run(function (string $userId, Vin $vin, AccessToken $token): void {
            $this->service->honkHorn($userId, $vin, $token);
        });
    }

    public function flash(): never
    {
        $this->run(function (string $userId, Vin $vin, AccessToken $token): void {
            $this->service->flashLights($userId, $vin, $token);
        });
    }

    public function trunkFront(): never
    {
        $this->run(function (string $userId, Vin $vin, AccessToken $token): void {
            $this->service->actuateTrunk($userId, $vin, TrunkSide::Front, $token);
        });
    }

    public function trunkRear(): never
    {
        $this->run(function (string $userId, Vin $vin, AccessToken $token): void {
            $this->service->actuateTrunk($userId, $vin, TrunkSide::Rear, $token);
        });
    }

    public function chargePortOpen(): never
    {
        $this->run(function (string $userId, Vin $vin, AccessToken $token): void {
            $this->service->openChargePortDoor($userId, $vin, $token);
        });
    }

    public function chargePortClose(): never
    {
        $this->run(function (string $userId, Vin $vin, AccessToken $token): void {
            $this->service->closeChargePortDoor($userId, $vin, $token);
        });
    }

    public function wake(): never
    {
        $this->run(function (string $userId, Vin $vin, AccessToken $token): void {
            $this->service->wakeUp($userId, $vin, $token);
        });
    }

    /**
     * Applies the shared guards, builds the command context from the session,
     * runs the command and maps the outcome to a JSON response. Every path ends
     * with Http::json() (which exits), hence the `never` return type.
     *
     * @param callable(string, Vin, AccessToken): void $command
     */
    private function run(callable $command): never
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            Http::json(['error' => 'Method not allowed'], 405);
        }

        $userId = $_SESSION['user_id'] ?? '';
        $token = $_SESSION['access_token'] ?? '';
        $vin = $_SESSION['selected_vin'] ?? '';

        if (!is_string($userId) || $userId === '' || !is_string($token) || $token === '') {
            Http::json(['error' => 'Authentication required'], 401);
        }

        if (!Csrf::checkFromHeader()) {
            Http::json(['error' => 'Invalid CSRF token'], 403);
        }

        if (!is_string($vin) || $vin === '') {
            Http::json(['error' => 'No vehicle selected'], 400);
        }

        try {
            $command($userId, new Vin($vin), new AccessToken($token));
        } catch (VehicleUnauthorizedException) {
            Http::json(['error' => 'You do not have access to this vehicle'], 403);
        } catch (TeslaApiException) {
            Http::json(['error' => 'The vehicle command failed'], 503);
        }

        Http::json(['success' => true]);
    }
}
