<?php

declare(strict_types=1);

namespace Teslapp\Controllers;

use Teslapp\Models\Shared\VehicleTelemetryRepository;
use Teslapp\Models\Vehicle\VehicleRepositoryInterface;
use Teslapp\Utils\Http;
use Teslapp\Utils\Route;

/**
 * Displays the vehicle dashboard: pulls the latest telemetry for the selected
 * vehicle and hands it to the view. All persistence lives in the repositories.
 */
final readonly class DashboardController
{
    public function __construct(
        private VehicleTelemetryRepository $telemetry,
        private VehicleRepositoryInterface $vehicles,
    ) {}

    public function index(): void
    {
        $vehicleId = Route::param('vehicleId');
        $userId = (string) ($_SESSION['user_id'] ?? '');

        $vehicle = $this->vehicles->findByPublicId($vehicleId);
        if ($vehicle === null || !$vehicle->isAccessibleBy($userId)) {
            Http::redirect('/dashboard');
        }

        $vin = $vehicle->vin;
        $data = $this->telemetry->getLatestTelemetry($vin);
        $vehicleName = $vehicle->name !== '' ? $vehicle->name : 'Mon véhicule';

        require_once __DIR__ . '/../Views/Vehicle/dashboard.php';
    }
}
