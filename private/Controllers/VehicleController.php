<?php

declare(strict_types=1);

namespace Teslapp\Controllers;

use Teslapp\Models\Shared\Exceptions\TeslaAppException;
use Teslapp\Models\Shared\ValueObjects\AccessToken;
use Teslapp\Models\Shared\ValueObjects\VehicleConnectivityStatus;
use Teslapp\Models\Vehicle\Vehicle;
use Teslapp\Models\Vehicle\VehicleService;
use Teslapp\Utils\Csrf;
use Teslapp\Utils\Flash;
use Teslapp\Utils\Http;

/**
 * Lists the user's vehicles and lets them pick the one to control.
 */
final class VehicleController
{
    public function __construct(private readonly VehicleService $vehicleService) {}

    /**
     * GET vehicle/select — refresh from the Tesla API when a token is available, then show the list.
     */
    public function select(): void
    {
        $token = isset($_SESSION['access_token'])
            ? new AccessToken($_SESSION['access_token'])
            : null;

        $statuses = [];

        if ($token !== null) {
            try {
                $this->vehicleService->syncUserVehicles($_SESSION['user_id'], $token);
            } catch (TeslaAppException $e) {
                // Tesla unreachable (no token yet, vehicle offline...): fall back to the stored list.
                error_log('Vehicle sync failed: ' . $e->getMessage());
                Flash::set(
                    'info',
                    'Synchronisation Tesla indisponible, affichage des véhicules connus.',
                );
            }

            try {
                $statuses = $this->vehicleService->connectivityForUser($token);
            } catch (TeslaAppException $e) {
                // Live status is optional: show the cards without a dot if it fails.
                error_log('Connectivity fetch failed: ' . $e->getMessage());
            }
        }

        $selectedVin = $_SESSION['selected_vin'] ?? null;
        $cards = [];
        foreach ($this->vehicleService->listForUser($_SESSION['user_id']) as $vehicle) {
            $model = $this->vehicleService->modelNameForVin($vehicle->vin);
            $cards[] = [
                'vehicle' => $vehicle,
                'model' => $model,
                'image' => $this->modelImage($model),
                'status' => $statuses[$vehicle->vin->value] ?? VehicleConnectivityStatus::Unknown,
                'selected' => $vehicle->vin->value === $selectedVin,
            ];
        }

        $csrfToken = $_SESSION['csrf_token'] ?? '';

        require_once __DIR__ . '/../Views/Vehicle/select.php';
    }

    /** Web path to the model image, or '' when the file is missing (the view shows a fallback). */
    private function modelImage(string $modelName): string
    {
        $file = '/_assets/images/' . str_replace(' ', '-', strtolower($modelName)) . '.png';

        return is_file(__DIR__ . '/../../www' . $file) ? $file : '';
    }

    /**
     * POST vehicle/choose — remember the chosen VIN in the session.
     */
    public function choose(): void
    {
        Csrf::requireValid('/vehicle/select');

        $vin = filter_input(INPUT_POST, 'vin', FILTER_UNSAFE_RAW);
        $vin = is_string($vin) ? trim($vin) : '';

        // Only a VIN the user actually owns can be selected (do not trust the POST).
        $owned = array_filter(
            $this->vehicleService->listForUser($_SESSION['user_id']),
            static fn(Vehicle $v): bool => $v->vin->value === $vin,
        );

        if ($owned === []) {
            Flash::set('errors', ['Véhicule inconnu.']);
            Http::redirect('/vehicle/select');
        }

        $_SESSION['selected_vin'] = $vin;
        Flash::set('success', 'Véhicule sélectionné.');
        Http::redirect('/vehicle/dashboard');
    }
}
