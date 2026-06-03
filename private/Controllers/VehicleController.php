<?php

declare(strict_types=1);

namespace Teslapp\Controllers;

use Teslapp\Models\Shared\Exceptions\TeslaAppException;
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
    public function __construct(
        private readonly VehicleService $vehicleService,
    ) {
    }

    /**
     * GET vehicle/select — refresh from the Tesla API (best effort), then show the list.
     */
    public function select(): void
    {
        try {
            $this->vehicleService->syncUserVehicles(DEV_USER_ID);
        } catch (TeslaAppException $e) {
            // Tesla unreachable (no token yet, vehicle offline...): fall back to the stored list.
            error_log('Vehicle sync failed: ' . $e->getMessage());
            Flash::set('info', 'Synchronisation Tesla indisponible, affichage des véhicules connus.');
        }

        $vehicles = $this->vehicleService->listForUser(DEV_USER_ID);
        $selectedVin = $_SESSION['selected_vin'] ?? null;

        require_once __DIR__ . '/../Views/Vehicle/select.php';
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
            $this->vehicleService->listForUser(DEV_USER_ID),
            static fn (Vehicle $v): bool => $v->vin->value === $vin,
        );

        if ($owned === []) {
            Flash::set('errors', ['Véhicule inconnu.']);
            Http::redirect('/vehicle/select');
        }

        $_SESSION['selected_vin'] = $vin;
        Flash::set('success', 'Véhicule sélectionné.');
        Http::redirect('/vehicle/select');
    }
}
