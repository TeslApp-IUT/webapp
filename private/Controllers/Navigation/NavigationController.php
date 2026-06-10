<?php

declare(strict_types=1);

namespace Teslapp\Controllers\Navigation;

use InvalidArgumentException;
use Teslapp\Models\Shared\Exceptions\VehicleUnauthorizedException;
use Teslapp\Models\Shared\VehicleTelemetryRepository;
use Teslapp\Models\Vehicle\VehicleRepositoryInterface;
use Teslapp\Utils\Flash;
use Teslapp\Utils\Http;
use Teslapp\Utils\Route;

/**
 * Controller for the navigation page.
 * Displays navigation-related information for the selected vehicle.
 */
final class NavigationController
{
    public function __construct(
        private readonly VehicleTelemetryRepository $telemetryRepository,
        private readonly VehicleRepositoryInterface $vehicles,
    ) {}

    /**
     * GET dashboard/{vehicleId}/navigation
     * Displays the navigation page with the latest navigation data for the selected vehicle.
     **/
    public function page(): void
    {
        ['userId' => $userId, 'vin' => $vin, 'vehicleId' => $vehicleId] = $this->requireVehicle();

        try {
            $data = $this->telemetryRepository->getLatestTelemetry($vin);
        } catch (InvalidArgumentException | VehicleUnauthorizedException) {
            Flash::set('error', 'Véhicule invalide ou inaccessible.');
            Http::redirect('/dashboard');
        }

        require_once __DIR__ . '/../../Views/Navigation/navigation.php';
    }

    /**
     * Resolves the vehicle targeted by the {vehicleId} route parameter.
     *
     * @return array{userId: string, vin: \Teslapp\Models\Shared\ValueObjects\Vin, vehicleId: string}
     */
    private function requireVehicle(): array
    {
        $vehicleId = Route::param('vehicleId');
        return $this->resolveVehicle($vehicleId) + ['vehicleId' => $vehicleId];
    }

    /**
     * @return array{userId: string, vin: \Teslapp\Models\Shared\ValueObjects\Vin}
     */
    private function resolveVehicle(string $vehicleId): array
    {
        $userId = (string) ($_SESSION['user_id'] ?? '');
        $vehicle = $this->vehicles->findByPublicId($vehicleId);
        if ($vehicle === null || !$vehicle->isAccessibleBy($userId)) {
            Http::redirect('/dashboard');
        }
        return ['userId' => $userId, 'vin' => $vehicle->vin];
    }
}
