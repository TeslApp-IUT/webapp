<?php

declare(strict_types=1);

namespace Teslapp\Controllers\Climate;

use InvalidArgumentException;
use Teslapp\Models\Climate\ClimateService;
use Teslapp\Models\Climate\PreconditioningService;
use Teslapp\Models\Climate\ValueObjects\ClimateAction;
use Teslapp\Models\Climate\ValueObjects\KeeperMode;
use Teslapp\Models\Climate\ValueObjects\Temperature;
use Teslapp\Models\Shared\Exceptions\TeslaAppException;
use Teslapp\Models\Shared\Exceptions\VehicleUnauthorizedException;
use Teslapp\Models\Shared\ValueObjects\Vin;
use Teslapp\Models\Shared\VehicleTelemetryRepository;
use Teslapp\Models\Vehicle\VehicleRepositoryInterface;
use Teslapp\Utils\Csrf;
use Teslapp\Utils\Flash;
use Teslapp\Utils\Http;
use Teslapp\Utils\Route;
use Teslapp\Models\Climate\ValueObjects\CopTemp;

/**
 * Controller responsible for climate-related commands.
 * Handles climate activation, deactivation, keeper mode and preconditioning plans.
 **/
final class ClimateController
{
    public function __construct(
        private readonly ClimateService             $climateService,
        private readonly PreconditioningService     $preconditioningService,
        private readonly VehicleRepositoryInterface $vehicles,
        private readonly VehicleTelemetryRepository $telemetryRepository,
    ) {}

    /**
     * GET dashboard/{vehicleId}/ac
     * Displays the climate control page with the preconditioning plans for the selected vehicle.
     * Redirects to vehicle selection if the vehicle is invalid or unauthorized.
     *
     * @throws \Exception
     **/
    public function ac(): void
    {
        ['userId' => $userId, 'vin' => $vin, 'vehicleId' => $vehicleId] = $this->requireVehicle();

        try {
            $plans = $this->preconditioningService->listPlansForVehicle($userId, $vin);
        } catch (InvalidArgumentException|VehicleUnauthorizedException) {
            Flash::set('error', 'Véhicule invalide ou inaccessible.');
            Http::redirect('/dashboard');
        }

        $data = $this->telemetryRepository->latest('temp_int', 'inside_temp', $vin);

        require_once __DIR__ . '/../../Views/Climate/ac.php';
    }

    /**
     * POST climate/toggle
     * Activates or deactivates the climate system based on the 'action' POST parameter.
     * When starting, optionally applies the requested temperature.
     * Redirects to the climate page after the command is sent.
     **/
    public function toggle(): void
    {
        $vehicleId = (string)(filter_input(INPUT_POST, 'vehicle_id', FILTER_UNSAFE_RAW) ?? '');
        $page = '/dashboard/' . $vehicleId . '/ac';
        Csrf::requireValid($page);

        ['userId' => $userId, 'vin' => $vin] = $this->resolveVehicle($vehicleId);

        $action = ClimateAction::tryFrom(
            filter_input(INPUT_POST, 'action', FILTER_UNSAFE_RAW) ?? '',
        );

        if ($action === null) {
            Http::redirect($page);
        }

        try {
            if ($action === ClimateAction::Start) {
                $raw = filter_input(INPUT_POST, 'temperature', FILTER_VALIDATE_FLOAT);
                $temp = $raw !== false ? new Temperature($raw) : null;
                $this->climateService->activate($userId, $vin, $temp);
            } else {
                $this->climateService->deactivate($userId, $vin);
            }

            Flash::set('success', 'Commande envoyée.');
        } catch (TeslaAppException $e) {
            error_log('Climate toggle failed: ' . $e->getMessage());
            Flash::set('error', 'Impossible d\'envoyer la commande à Tesla.');
        }

        Http::redirect($page);
    }

    /**
     * POST climate/keeper
     * Sets the climate keeper mode for the selected vehicle
     * Modes: 0 = Off, 1 = Keep, 2 = Dog, 3 = Camp
     **/
    public function setKeeperMode(): void
    {
        $vehicleId = (string)(filter_input(INPUT_POST, 'vehicle_id', FILTER_UNSAFE_RAW) ?? '');
        $page = '/dashboard/' . $vehicleId . '/ac';
        Csrf::requireValid($page);

        ['userId' => $userId, 'vin' => $vin] = $this->resolveVehicle($vehicleId);

        $raw = filter_input(INPUT_POST, 'climate_keeper_mode', FILTER_VALIDATE_INT);
        $mode = $raw !== false ? KeeperMode::tryFrom($raw) : null;

        if ($mode === null) {
            Flash::set('error', 'Mode invalide.');
            Http::redirect($page);
        }

        try {
            $this->climateService->applyKeeperMode($userId, $vin, $mode);
            Flash::set('success', 'Mode keeper appliqué.');
        } catch (TeslaAppException $e) {
            error_log('Keeper mode failed: ' . $e->getMessage());
            Flash::set('error', 'Impossible d\'appliquer le mode keeper.');
        }

        if ($mode === KeeperMode::Keep) {
            $rawCop = filter_input(INPUT_POST, 'cop_temp', FILTER_VALIDATE_INT);
            $copTemp = $rawCop !== false ? CopTemp::tryFrom($rawCop) : null;

            if ($copTemp !== null) {
                try {
                    $this->climateService->applyCopTemp($userId, $vin, $copTemp);
                } catch (TeslaAppException $e) {
                    error_log('COP temp failed: ' . $e->getMessage());
                }
            }
        }

        Http::redirect($page);
    }

    /**
     * Resolves the vehicle targeted by the {vehicleId} route parameter.
     *
     * @return array{userId: string, vin: Vin, vehicleId: string}
     **/
    private function requireVehicle(): array
    {
        $vehicleId = Route::param('vehicleId');
        return $this->resolveVehicle($vehicleId) + ['vehicleId' => $vehicleId];
    }

    /** @return array{userId: string, vin: Vin} */
    private function resolveVehicle(string $vehicleId): array
    {
        $userId = (string)($_SESSION['user_id'] ?? '');
        $vehicle = $this->vehicles->findByPublicId($vehicleId);
        if ($vehicle === null || !$vehicle->isAccessibleBy($userId)) {
            Http::redirect('/dashboard');
        }
        return ['userId' => $userId, 'vin' => $vehicle->vin];
    }
}
