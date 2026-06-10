<?php

declare(strict_types=1);

namespace Teslapp\Controllers\Charging;

use InvalidArgumentException;
use Teslapp\Models\Charging\ChargingPlannerService;
use Teslapp\Models\Charging\ChargingService;
use Teslapp\Models\Charging\ValueObjects\ChargeLimit;
use Teslapp\Models\Charging\ValueObjects\ChargingAction;
use Teslapp\Models\Charging\ValueObjects\ChargingAmps;
use Teslapp\Models\Shared\Exceptions\TeslaAppException;
use Teslapp\Models\Shared\Exceptions\VehicleUnauthorizedException;
use Teslapp\Models\Shared\TeslaApi\VehicleTelemetryRepositoryInterface;
use Teslapp\Models\Shared\ValueObjects\Vin;
use Teslapp\Models\Vehicle\VehicleRepositoryInterface;
use Teslapp\Utils\Csrf;
use Teslapp\Utils\Flash;
use Teslapp\Utils\Http;
use Teslapp\Utils\Route;

/**
 * Battery page (dashboard/{vehicleId}/battery) and immediate charging commands.
 *
 * battery() renders the page. toggle(), setLimit() and setAmps() are POST actions
 * routed at static paths (charging/*); they read vehicle_id from the POST body to
 * resolve the vehicle and construct the redirect URL.
 */
final class ChargingController
{
    public function __construct(
        private readonly ChargingService $chargingService,
        private readonly ChargingPlannerService $plannerService,
        private readonly VehicleTelemetryRepositoryInterface $telemetry,
        private readonly VehicleRepositoryInterface $vehicles,
    ) {}

    public function battery(): void
    {
        ['userId' => $userId, 'vin' => $vin, 'vehicleId' => $vehicleId] = $this->requireVehicle();

        try {
            $plans = $this->plannerService->listPlansForVehicle($userId, $vin);
            $data = $this->telemetry->getLatestTelemetry($vin);
        } catch (InvalidArgumentException | VehicleUnauthorizedException) {
            Flash::set('errors', ['Véhicule invalide ou inaccessible.']);
            Http::redirect('/dashboard');
        }

        require_once __DIR__ . '/../../Views/Charging/battery.php';
    }

    public function toggle(): void
    {
        $vehicleId = (string) (filter_input(INPUT_POST, 'vehicle_id', FILTER_UNSAFE_RAW) ?? '');
        $page = '/dashboard/' . $vehicleId . '/battery';
        Csrf::requireValid($page);

        ['userId' => $userId, 'vin' => $vin] = $this->resolveVehicle($vehicleId);

        $action = ChargingAction::tryFrom(
            filter_input(INPUT_POST, 'action', FILTER_UNSAFE_RAW) ?? '',
        );

        if ($action === null) {
            Http::redirect($page);
        }

        try {
            $action === ChargingAction::Start
                ? $this->chargingService->start($userId, $vin)
                : $this->chargingService->stop($userId, $vin);

            Flash::set('success', 'Commande envoyée.');
        } catch (TeslaAppException $e) {
            error_log('Charging toggle failed: ' . $e->getMessage());
            Flash::set('errors', ['Impossible d\'envoyer la commande à Tesla.']);
        }

        Http::redirect($page);
    }

    public function setLimit(): void
    {
        $vehicleId = (string) (filter_input(INPUT_POST, 'vehicle_id', FILTER_UNSAFE_RAW) ?? '');
        $page = '/dashboard/' . $vehicleId . '/battery';
        Csrf::requireValid($page);

        ['userId' => $userId, 'vin' => $vin] = $this->resolveVehicle($vehicleId);

        $raw = filter_input(INPUT_POST, 'percent', FILTER_VALIDATE_INT);

        try {
            if (!is_int($raw)) {
                throw new InvalidArgumentException('Invalid charge limit');
            }

            $this->chargingService->setChargeLimit($userId, $vin, new ChargeLimit($raw));
            Flash::set('success', 'Limite de charge appliquée.');
        } catch (InvalidArgumentException) {
            Flash::set('errors', ['Limite de charge invalide (50 à 100 %).']);
        } catch (TeslaAppException $e) {
            error_log('Charge limit failed: ' . $e->getMessage());
            Flash::set('errors', ['Impossible d\'envoyer la commande à Tesla.']);
        }

        Http::redirect($page);
    }

    public function setAmps(): void
    {
        $vehicleId = (string) (filter_input(INPUT_POST, 'vehicle_id', FILTER_UNSAFE_RAW) ?? '');
        $page = '/dashboard/' . $vehicleId . '/battery';
        Csrf::requireValid($page);

        ['userId' => $userId, 'vin' => $vin] = $this->resolveVehicle($vehicleId);

        $raw = filter_input(INPUT_POST, 'amps', FILTER_VALIDATE_INT);

        try {
            if (!is_int($raw)) {
                throw new InvalidArgumentException('Invalid charging amps');
            }

            $this->chargingService->setChargingAmps($userId, $vin, new ChargingAmps($raw));
            Flash::set('success', 'Ampérage appliqué.');
        } catch (InvalidArgumentException) {
            Flash::set('errors', ['Ampérage invalide (5 à 48 A).']);
        } catch (TeslaAppException $e) {
            error_log('Charging amps failed: ' . $e->getMessage());
            Flash::set('errors', ['Impossible d\'envoyer la commande à Tesla.']);
        }

        Http::redirect($page);
    }

    /** @return array{userId: string, vin: Vin, vehicleId: string} */
    private function requireVehicle(): array
    {
        $vehicleId = Route::param('vehicleId');
        return $this->resolveVehicle($vehicleId) + ['vehicleId' => $vehicleId];
    }

    /** @return array{userId: string, vin: Vin} */
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
