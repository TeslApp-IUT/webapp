<?php

declare(strict_types=1);

namespace Teslapp\Controllers\Charging;

use InvalidArgumentException;
use Teslapp\Models\Charging\ChargingPlannerService;
use Teslapp\Models\Shared\Exceptions\TeslaApiException;
use Teslapp\Models\Shared\Exceptions\VehicleAsleepException;
use Teslapp\Models\Shared\Exceptions\VehicleUnauthorizedException;
use Teslapp\Models\Shared\ValueObjects\DayOfWeek;
use Teslapp\Models\Shared\ValueObjects\GeoPoint;
use Teslapp\Models\Shared\ValueObjects\Vin;
use Teslapp\Models\Vehicle\VehicleRepositoryInterface;
use Teslapp\Utils\Csrf;
use Teslapp\Utils\Flash;
use Teslapp\Utils\Http;
use Teslapp\Utils\Route;

/**
 * Charging schedule write actions on the battery page (dashboard/{vehicleId}/battery).
 *
 * create/update/delete/toggle are POST actions sharing the same shape: check the CSRF
 * token, resolve the vehicle from the URL param, call ChargingPlannerService, set a flash
 * message, then redirect back. Pure HTTP orchestration: no business rule and no SQL here.
 */
final class ChargingPlannerController
{
    private const ASLEEP_MESSAGE = 'Le véhicule ne s\'est pas réveillé à temps. Réessayez dans un instant.';

    public function __construct(
        private readonly ChargingPlannerService $service,
        private readonly VehicleRepositoryInterface $vehicles,
    ) {}

    public function create(): void
    {
        $vehicleId = Route::param('vehicleId');
        $page = '/dashboard/' . $vehicleId . '/battery';
        Csrf::requireValid($page);

        ['userId' => $userId, 'vin' => $vin] = $this->resolveVehicle($vehicleId);

        try {
            $this->service->createPlan(
                $userId,
                $vin,
                $this->readHour('activation_hour'),
                $this->readOptionalHour('deactivation_hour'),
                $this->readDays(),
                $this->boolField('memorize'),
                $this->boolField('enabled'),
                $this->readLocation(),
                $this->post('location_label') ?: null,
            );
            Flash::set('success', 'Planification créée.');
        } catch (InvalidArgumentException) {
            Flash::set('errors', ['Saisie invalide (heure ou coordonnées).']);
        } catch (VehicleUnauthorizedException) {
            Flash::set('errors', ['Vous n\'avez pas accès à ce véhicule.']);
        } catch (VehicleAsleepException) {
            // The service already woke the vehicle and retried; it needs more time.
            Flash::set('errors', [self::ASLEEP_MESSAGE]);
        } catch (TeslaApiException) {
            Flash::set('errors', ['La commande Tesla a échoué.']);
        }

        Http::redirect($page);
    }

    public function update(): void
    {
        $vehicleId = Route::param('vehicleId');
        $page = '/dashboard/' . $vehicleId . '/battery';
        Csrf::requireValid($page);

        ['userId' => $userId, 'vin' => $vin] = $this->resolveVehicle($vehicleId);

        try {
            $this->service->updatePlan(
                $userId,
                $vin,
                $this->post('plan_id'),
                $this->readHour('activation_hour'),
                $this->readOptionalHour('deactivation_hour'),
                $this->readDays(),
                $this->boolField('memorize'),
                $this->boolField('enabled'),
                $this->readLocation(),
                $this->post('location_label') ?: null,
            );
            Flash::set('success', 'Planification mise à jour.');
        } catch (InvalidArgumentException) {
            Flash::set('errors', ['Saisie invalide (heure ou coordonnées).']);
        } catch (VehicleUnauthorizedException) {
            Flash::set('errors', ['Vous n\'avez pas accès à ce véhicule.']);
        } catch (VehicleAsleepException) {
            // The service already woke the vehicle and retried; it needs more time.
            Flash::set('errors', [self::ASLEEP_MESSAGE]);
        } catch (TeslaApiException) {
            Flash::set('errors', ['La commande Tesla a échoué.']);
        }

        Http::redirect($page);
    }

    public function delete(): void
    {
        $vehicleId = Route::param('vehicleId');
        $page = '/dashboard/' . $vehicleId . '/battery';
        Csrf::requireValid($page);

        ['userId' => $userId, 'vin' => $vin] = $this->resolveVehicle($vehicleId);

        try {
            $this->service->deletePlan($userId, $vin, $this->post('plan_id'));
            Flash::set('success', 'Planification supprimée.');
        } catch (InvalidArgumentException) {
            Flash::set('errors', ['Saisie invalide.']);
        } catch (VehicleUnauthorizedException) {
            Flash::set('errors', ['Vous n\'avez pas accès à ce véhicule.']);
        } catch (VehicleAsleepException) {
            // The service already woke the vehicle and retried; it needs more time.
            Flash::set('errors', [self::ASLEEP_MESSAGE]);
        } catch (TeslaApiException) {
            Flash::set('errors', ['La commande Tesla a échoué.']);
        }

        Http::redirect($page);
    }

    public function toggle(): void
    {
        $vehicleId = Route::param('vehicleId');
        $page = '/dashboard/' . $vehicleId . '/battery';
        Csrf::requireValid($page);

        ['userId' => $userId, 'vin' => $vin] = $this->resolveVehicle($vehicleId);

        try {
            $this->service->setPlanEnabled(
                $userId,
                $vin,
                $this->post('plan_id'),
                $this->boolField('enabled'),
            );
        } catch (InvalidArgumentException) {
            Flash::set('errors', ['Saisie invalide.']);
        } catch (VehicleUnauthorizedException) {
            Flash::set('errors', ['Vous n\'avez pas accès à ce véhicule.']);
        }

        Http::redirect($page);
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

    private function readHour(string $field): string
    {
        $hour = $this->post($field);
        if (preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $hour) !== 1) {
            throw new InvalidArgumentException("Invalid $field");
        }

        return $hour;
    }

    private function readOptionalHour(string $field): ?string
    {
        return $this->post($field) === '' ? null : $this->readHour($field);
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
