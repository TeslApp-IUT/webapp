<?php
declare(strict_types=1);

namespace Teslapp\Controllers\Climate;

use Teslapp\Models\Climate\ClimateService;
use Teslapp\Models\Climate\ValueObjects\ClimateAction;
use Teslapp\Models\Climate\ValueObjects\KeeperMode;
use Teslapp\Models\Climate\ValueObjects\Temperature;
use Teslapp\Models\Shared\Exceptions\TeslaAppException;
use Teslapp\Models\Shared\ValueObjects\Vin;
use Teslapp\Utils\Csrf;
use Teslapp\Utils\Flash;
use Teslapp\Utils\Http;

final class ClimateController
{
    public function __construct(private readonly ClimateService $climateService) {}

    /**
     * GET dashboard/ac
     * Displays the climate page
     **/
    public function ac(): void
    {
        if (!isset($_SESSION['selected_vin'])) {
            Http::redirect('/vehicle/select');
        }

        require_once __DIR__ . '/../../Views/Climate/ac.php';
    }

    /**
     * Handles climate activation and deactivation
     * When action is 'start', activates the climate and optionally sets the temperature
     * When action is 'stop', deactivates the climate
     * Redirects to the actual page
     **/
    public function toggle(): void
    {
        Csrf::requireValid('/dashboard/ac');

        ['userId' => $userId, 'vin' => $vin] = $this->requireSession();

        $action = ClimateAction::tryFrom(
            filter_input(INPUT_POST, 'action', FILTER_UNSAFE_RAW) ?? '',
        );

        if ($action === null) {
            Http::redirect('/dashboard/ac');
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

        Http::redirect('/dashboard/ac');
    }

    /**
     * POST climate/keeper
     * Sets the climate keeper mode : 0 = Off, 1 = Keep, 2 = Dog, 3 = Camp
     **/
    public function setKeeperMode(): void
    {
        Csrf::requireValid('/dashboard/ac');

        ['userId' => $userId, 'vin' => $vin] = $this->requireSession();

        $raw = filter_input(INPUT_POST, 'climate_keeper_mode', FILTER_VALIDATE_INT);
        $mode = $raw !== false ? KeeperMode::tryFrom($raw) : null;

        if ($mode === null) {
            Flash::set('error', 'Mode invalide.');
            Http::redirect('/dashboard/ac');
        }

        try {
            $this->climateService->applyKeeperMode($userId, $vin, $mode);
            Flash::set('success', 'Mode keeper appliqué.');
        } catch (TeslaAppException $e) {
            error_log('Keeper mode failed: ' . $e->getMessage());
            Flash::set('error', 'Impossible d\'appliquer le mode keeper.');
        }

        Http::redirect('/dashboard/ac');
    }

    /**
     * @return array{userId: string, vin: Vin}
     */
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
