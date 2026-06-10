<?php

declare(strict_types=1);

namespace Teslapp\Controllers;

use Teslapp\Models\Shared\Exceptions\TeslaApiException;
use Teslapp\Models\Shared\Exceptions\VehicleUnauthorizedException;
use Teslapp\Models\Shared\ValueObjects\TrunkSide;
use Teslapp\Models\Shared\ValueObjects\Vin;
use Teslapp\Models\Vehicle\VehicleCommandService;
use Teslapp\Models\Vehicle\VehicleRepositoryInterface;
use Teslapp\Utils\Csrf;
use Teslapp\Utils\Http;
use Teslapp\Utils\Route;

/**
 * JSON endpoints for the vehicle commands (issue #26): lock/unlock, horn,
 * headlight flash, front/rear trunk, charge port door and wake up.
 *
 * Each endpoint is a POST AJAX call answering JSON. The shared guards (HTTP
 * method, authenticated session, CSRF header, selected vehicle) and the
 * exception-to-status mapping live in run(). The Tesla access token is resolved
 * downstream by TeslaHttpClient (from the session), so it is not handled here.
 * Kept separate from VehicleController (read/selection) on purpose.
 */
final class VehicleCommandController
{
    public function __construct(
        private readonly VehicleCommandService $service,
        private readonly VehicleRepositoryInterface $vehicles,
    ) {}

    /**
     * Renders the vehicle command page (GET) — the "Véhicule" dashboard tab.
     */
    public function page(): void
    {
        $vehicleId = Route::param('vehicleId');
        $userId = (string) ($_SESSION['user_id'] ?? '');

        $vehicle = $this->vehicles->findByPublicId($vehicleId);
        if ($vehicle === null || !$vehicle->isAccessibleBy($userId)) {
            Http::redirect('/dashboard');
        }

        require_once __DIR__ . '/../Views/Vehicle/control.php';
    }

    public function lock(): never
    {
        $this->run(function (string $userId, Vin $vin): void {
            $this->service->lock($userId, $vin);
        });
    }

    public function unlock(): never
    {
        $this->run(function (string $userId, Vin $vin): void {
            $this->service->unlock($userId, $vin);
        });
    }

    public function honk(): never
    {
        $this->run(function (string $userId, Vin $vin): void {
            $this->service->honkHorn($userId, $vin);
        });
    }

    public function flash(): never
    {
        $this->run(function (string $userId, Vin $vin): void {
            $this->service->flashLights($userId, $vin);
        });
    }

    public function trunkFront(): never
    {
        $this->run(function (string $userId, Vin $vin): void {
            $this->service->actuateTrunk($userId, $vin, TrunkSide::Front);
        });
    }

    public function trunkRear(): never
    {
        $this->run(function (string $userId, Vin $vin): void {
            $this->service->actuateTrunk($userId, $vin, TrunkSide::Rear);
        });
    }

    public function chargePortOpen(): never
    {
        $this->run(function (string $userId, Vin $vin): void {
            $this->service->openChargePortDoor($userId, $vin);
        });
    }

    public function chargePortClose(): never
    {
        $this->run(function (string $userId, Vin $vin): void {
            $this->service->closeChargePortDoor($userId, $vin);
        });
    }

    public function wake(): never
    {
        $this->run(function (string $userId, Vin $vin): void {
            $this->service->wakeUp($userId, $vin);
        });
    }

    /**
     * @param callable(string, Vin): void $command
     */
    private function run(callable $command): never
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            Http::json(['error' => 'Method not allowed'], 405);
        }

        $userId = $_SESSION['user_id'] ?? '';

        if (!is_string($userId) || $userId === '') {
            Http::json(['error' => 'Authentication required'], 401);
        }

        if (!Csrf::checkFromHeader()) {
            Http::json(['error' => 'Invalid CSRF token'], 403);
        }

        $vehicleId = Route::param('vehicleId');
        $vehicle = $this->vehicles->findByPublicId($vehicleId);

        if ($vehicle === null || !$vehicle->isAccessibleBy($userId)) {
            Http::json(['error' => 'No vehicle selected'], 400);
        }

        try {
            $command($userId, $vehicle->vin);
        } catch (VehicleUnauthorizedException) {
            Http::json(['error' => 'You do not have access to this vehicle'], 403);
        } catch (TeslaApiException) {
            Http::json(['error' => 'The vehicle command failed'], 503);
        }

        Http::json(['success' => true]);
    }
}
