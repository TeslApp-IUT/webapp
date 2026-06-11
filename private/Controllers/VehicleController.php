<?php

declare(strict_types=1);

namespace Teslapp\Controllers;

use Teslapp\Models\Shared\Exceptions\TeslaAppException;
use Teslapp\Models\Shared\ValueObjects\VehicleConnectivityStatus;
use Teslapp\Models\Vehicle\VehicleService;
use Teslapp\Utils\Flash;

/**
 * Lists the user's vehicles so the user can navigate to one.
 */
final readonly class VehicleController
{
    public function __construct(private readonly VehicleService $vehicleService) {}

    /**
     * GET dashboard — refresh from the Tesla API when a token is available, then show the list.
     */
    public function select(): void
    {
        $statuses = [];

        try {
            $this->vehicleService->syncUserVehicles($_SESSION['user_id']);
        } catch (TeslaAppException $e) {
            error_log('Vehicle sync failed: ' . $e->getMessage());
            Flash::set(
                'info',
                'Synchronisation Tesla indisponible, affichage des véhicules connus.',
            );
        }

        try {
            $statuses = $this->vehicleService->connectivityForUser();
        } catch (TeslaAppException $e) {
            error_log('Connectivity fetch failed: ' . $e->getMessage());
        }

        $cards = [];
        foreach ($this->vehicleService->listForUser($_SESSION['user_id']) as $vehicle) {
            $model = $this->vehicleService->modelNameForVin($vehicle->vin);
            $cards[] = [
                'vehicle' => $vehicle,
                'model' => $model,
                'image' => $this->modelImage($model),
                'status' => $statuses[$vehicle->vin->value] ?? VehicleConnectivityStatus::Unknown,
            ];
        }

        require_once __DIR__ . '/../Views/Vehicle/select.php';
    }

    /** Web path to the model image, or '' when the file is missing (the view shows a fallback). */
    private function modelImage(string $modelName): string
    {
        return '/_assets/images/' . str_replace(' ', '-', strtolower($modelName)) . '.png';
    }
}
