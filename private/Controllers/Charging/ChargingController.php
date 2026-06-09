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
use Teslapp\Utils\Csrf;
use Teslapp\Utils\Flash;
use Teslapp\Utils\Http;

/**
 * Battery page (dashboard/battery) and immediate charging commands.
 *
 * battery() renders the page with the telemetry snapshot and the charging schedules.
 * toggle(), setLimit() and setAmps() are POST actions sharing the same shape: check
 * the CSRF token, resolve the session context, call ChargingService, set a flash
 * message, then redirect back. Pure HTTP orchestration: no business rule here.
 */
final class ChargingController
{
    private const PAGE = '/dashboard/battery';

    public function __construct(
        private readonly ChargingService $chargingService,
        private readonly ChargingPlannerService $plannerService,
        private readonly VehicleTelemetryRepositoryInterface $telemetry,
    ) {}

    public function battery(): void
    {
        ['userId' => $userId, 'vin' => $vin] = $this->requireSession();

        try {
            $plans = $this->plannerService->listPlansForVehicle($userId, $vin);
            $data = $this->telemetry->getLatestTelemetry($vin);
        } catch (InvalidArgumentException | VehicleUnauthorizedException) {
            Flash::set('errors', ['Véhicule invalide ou inaccessible.']);
            Http::redirect('/vehicle/select');
        }

        require_once __DIR__ . '/../../Views/Charging/battery.php';
    }

    public function toggle(): void
    {
        Csrf::requireValid(self::PAGE);

        ['userId' => $userId, 'vin' => $vin] = $this->requireSession();

        $action = ChargingAction::tryFrom(
            filter_input(INPUT_POST, 'action', FILTER_UNSAFE_RAW) ?? '',
        );

        if ($action === null) {
            Http::redirect(self::PAGE);
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

        Http::redirect(self::PAGE);
    }

    public function setLimit(): void
    {
        Csrf::requireValid(self::PAGE);

        ['userId' => $userId, 'vin' => $vin] = $this->requireSession();

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

        Http::redirect(self::PAGE);
    }

    public function setAmps(): void
    {
        Csrf::requireValid(self::PAGE);

        ['userId' => $userId, 'vin' => $vin] = $this->requireSession();

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

        Http::redirect(self::PAGE);
    }

    /** @return array{userId: string, vin: Vin} */
    private function requireSession(): array
    {
        if (!isset($_SESSION['selected_vin'])) {
            Http::redirect('/vehicle/select');
        }

        return [
            'userId' => (string) ($_SESSION['user_id'] ?? ''),
            'vin' => new Vin($_SESSION['selected_vin']),
        ];
    }
}
