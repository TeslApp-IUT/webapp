<?php

declare(strict_types=1);

namespace Teslapp\Controllers\Climate;

use InvalidArgumentException;
use Teslapp\Models\Climate\ClimateService;
use Teslapp\Models\Shared\Exceptions\TeslaApiException;
use Teslapp\Models\Shared\Exceptions\VehicleUnauthorizedException;
use Teslapp\Models\Shared\ValueObjects\DayOfWeek;
use Teslapp\Models\Shared\ValueObjects\GeoPoint;
use Teslapp\Models\Shared\ValueObjects\Vin;
use Teslapp\Utils\Csrf;
use Teslapp\Utils\Flash;
use Teslapp\Utils\Http;

/**
 * Preconditioning schedules CRUD on the climate page (dashboard/ac/precondition).
 * Autonomous and separate from the AC on/off controller: reads the form, calls
 * ClimateService, then redirects back with a flash message.
 */
final class PreconditioningController
{
    private const PAGE = '/dashboard/ac/precondition';

    public function __construct(private readonly ClimateService $service) {}

    public function index(): void
    {
        [$userId, $vin] = $this->context();

        try {
            $plans = $this->service->listPlansForVehicle($userId, new Vin($vin));
        } catch (InvalidArgumentException | VehicleUnauthorizedException) {
            Flash::set('errors', ['Véhicule invalide ou inaccessible.']);
            Http::redirect('/vehicle/select');
        }

        require_once __DIR__ . '/../../Views/Climate/preconditioning.php';
    }

    public function create(): never
    {
        $this->handle(
            fn(string $userId, Vin $vin): string => $this->service->createPlan(
                $userId,
                $vin,
                $this->readActivationHour(),
                $this->readDays(),
                $this->boolField('memorize'),
                $this->boolField('enabled'),
                $this->readLocation(),
                $this->post('location_label') ?: null,
            ),
            'Planification créée.',
        );
    }

    public function update(): never
    {
        $this->handle(
            fn(string $userId, Vin $vin) => $this->service->updatePlan(
                $userId,
                $vin,
                $this->post('plan_id'),
                $this->readActivationHour(),
                $this->readDays(),
                $this->boolField('memorize'),
                $this->boolField('enabled'),
                $this->readLocation(),
                $this->post('location_label') ?: null,
            ),
            'Planification mise à jour.',
        );
    }

    public function delete(): never
    {
        $this->handle(
            fn(string $userId, Vin $vin) => $this->service->deletePlan($userId, $vin, $this->post('plan_id')),
            'Planification supprimée.',
        );
    }

    public function toggle(): never
    {
        $this->handle(
            fn(string $userId, Vin $vin) => $this->service->setPlanEnabled(
                $userId,
                $vin,
                $this->post('plan_id'),
                $this->boolField('enabled'),
            ),
            null,
        );
    }

    /**
     * Shared flow for the write actions: CSRF, session context, run the action,
     * map known failures to a flash, then redirect back to the page.
     *
     * @param callable(string, Vin): mixed $action
     */
    private function handle(callable $action, ?string $success): never
    {
        Csrf::requireValid(self::PAGE);
        [$userId, $vin] = $this->context();

        try {
            $action($userId, new Vin($vin));
            if ($success !== null) {
                Flash::set('success', $success);
            }
        } catch (InvalidArgumentException) {
            Flash::set('errors', ['Saisie invalide (heure ou coordonnées).']);
        } catch (VehicleUnauthorizedException) {
            Flash::set('errors', ['Vous n\'avez pas accès à ce véhicule.']);
        } catch (TeslaApiException) {
            Flash::set('errors', ['La commande Tesla a échoué.']);
        }

        Http::redirect(self::PAGE);
    }

    /** @return array{string, string} the user id and the selected VIN */
    private function context(): array
    {
        $userId = $_SESSION['user_id'] ?? '';
        $vin = $_SESSION['selected_vin'] ?? '';

        if (!is_string($userId) || $userId === '') {
            Http::redirect('/site/home');
        }
        if (!is_string($vin) || $vin === '') {
            Http::redirect('/vehicle/select');
        }

        return [$userId, $vin];
    }

    private function readActivationHour(): string
    {
        $hour = $this->post('activation_hour');
        if (preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $hour) !== 1) {
            throw new InvalidArgumentException('Invalid activation hour');
        }

        return $hour;
    }

    /** @return list<DayOfWeek> */
    private function readDays(): array
    {
        $raw = $_POST['days'] ?? [];
        if (!is_array($raw)) {
            return [];
        }

        $days = [];
        foreach ($raw as $value) {
            $day = DayOfWeek::tryFrom((int) $value);
            if ($day !== null) {
                $days[] = $day;
            }
        }

        return $days;
    }

    private function readLocation(): ?GeoPoint
    {
        $lat = $this->post('latitude');
        $lon = $this->post('longitude');

        if ($lat === '' && $lon === '') {
            return null;
        }
        if (!is_numeric($lat) || !is_numeric($lon)) {
            throw new InvalidArgumentException('Invalid coordinates');
        }

        return new GeoPoint((float) $lat, (float) $lon);
    }

    private function post(string $name): string
    {
        $value = filter_input(INPUT_POST, $name, FILTER_DEFAULT);

        return is_string($value) ? trim($value) : '';
    }

    private function boolField(string $name): bool
    {
        return filter_input(INPUT_POST, $name, FILTER_VALIDATE_BOOLEAN) === true;
    }
}
