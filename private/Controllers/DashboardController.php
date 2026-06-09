<?php

declare(strict_types=1);

namespace Teslapp\Controllers;

use Teslapp\Models\Shared\TeslaApi\VehicleTelemetryRepositoryInterface;
use Teslapp\Models\Shared\ValueObjects\Vin;
use Teslapp\Models\Shared\VehicleTelemetryRepository;
use Teslapp\Models\Vehicle\VehicleRepository;
use Teslapp\Models\Vehicle\VehicleRepositoryInterface;

/**
 * Displays the vehicle dashboard: pulls the latest telemetry for the selected
 * vehicle and hands it to the view. All persistence lives in the repositories.
 */
final readonly class DashboardController
{
    public function __construct(
        private VehicleTelemetryRepository $telemetry,
        private VehicleRepository $vehicles,
    ) {}

    /**
     * Requires an authenticated user with a selected vehicle, then renders the dashboard.
     */
    public function index(): void
    {
        $selectedVin = $_SESSION['selected_vin'] ?? null;
        if (!is_string($selectedVin) || $selectedVin === '') {
            header('Location: /vehicle/select', true, 302);
            exit();
        }

        try {
            $vin = new Vin($selectedVin);
        } catch (\InvalidArgumentException) {
            unset($_SESSION['selected_vin']);
            header('Location: /vehicle/select', true, 302);
            exit();
        }

        $data = $this->telemetry->getLatestTelemetry($vin);
        $vehicleName = $this->vehicles->findByVin($vin)?->name ?? 'Mon véhicule';

        require_once __DIR__ . '/../Views/Vehicle/dashboard.php';
    }
}
