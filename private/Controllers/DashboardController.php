<?php

declare(strict_types=1);

namespace Teslapp\Controllers;

use Teslapp\Models\Shared\TeslaApi\VehicleTelemetryRepositoryInterface;
use Teslapp\Models\Shared\ValueObjects\Vin;
use Teslapp\Models\Vehicle\VehicleRepositoryInterface;

/**
 * Displays the vehicle dashboard: pulls the latest telemetry for the selected
 * vehicle and hands it to the view. All persistence lives in the repositories.
 */
final class DashboardController
{
    public function __construct(
        private readonly VehicleTelemetryRepositoryInterface $telemetry,
        private readonly VehicleRepositoryInterface $vehicles,
    ) {}

    /**
     * Requires an authenticated user with a selected vehicle, then renders the dashboard.
     */
    public function index(): void
    {
        // Authentication is enforced centrally by the front controller (requiresAuth route flag).
        $selectedVin = $_SESSION['selected_vin'] ?? null;
        if (!is_string($selectedVin) || $selectedVin === '') {
            header('Location: /vehicle/select');
            exit();
        }

        try {
            $vin = new Vin($selectedVin);
        } catch (\InvalidArgumentException) {
            // A corrupted/stale selected_vin must not 500 the dashboard.
            unset($_SESSION['selected_vin']);
            header('Location: /vehicle/select');
            exit();
        }

        $data = $this->telemetry->getLatestTelemetry($vin);
        $vehicleName = $this->vehicles->findByVin($vin)?->name ?? 'Mon véhicule';

        require_once __DIR__ . '/../Views/Vehicle/dashboard.php';
    }
}
