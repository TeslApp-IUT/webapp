<?php

declare(strict_types=1);

namespace Teslapp\Controllers\Charging;

use InvalidArgumentException;
use Teslapp\Models\Charging\ChargingPlannerService;
use Teslapp\Models\Shared\Exceptions\TeslaApiException;
use Teslapp\Models\Shared\Exceptions\VehicleUnauthorizedException;
use Teslapp\Models\Shared\ValueObjects\DayOfWeek;
use Teslapp\Models\Shared\ValueObjects\GeoPoint;
use Teslapp\Models\Shared\ValueObjects\Vin;
use Teslapp\Utils\Csrf;
use Teslapp\Utils\Flash;
use Teslapp\Utils\Http;

/**
 * Charging schedule write actions on the battery page (dashboard/battery).
 *
 * create/update/delete/toggle are POST actions sharing the same shape: check the CSRF
 * token, resolve the session context, call ChargingPlannerService, set a flash message,
 * then redirect back. The schedules are listed by ChargingController on dashboard/battery.
 * Pure HTTP orchestration: no business rule and no SQL here.
 */
final class ChargingPlannerController
{
    private const PAGE = '/dashboard/battery';

    public function __construct(private readonly ChargingPlannerService $service) {}

    // Creates a charging window from the submitted form.
    public function create(): void
    {
        Csrf::requireValid(self::PAGE);
        [$userId, $vin] = $this->context();

        try {
            $this->service->createPlan(
                $userId,
                new Vin($vin),
                $this->readHour('activation_hour'),
                $this->readOptionalHour('deactivation_hour'),
                $this->readDays(),
                $this->boolField('memorize'),
                $this->boolField('enabled'),
                $this->readLocation(),
                $this->post('location_label') ?: null, // empty label stays null
            );
            Flash::set('success', 'Planification créée.');
        } catch (InvalidArgumentException) {
            // Invalid hour or coordinates in the form.
            Flash::set('errors', ['Saisie invalide (heure ou coordonnées).']);
        } catch (VehicleUnauthorizedException) {
            // The vehicle is not the logged-in user's.
            Flash::set('errors', ['Vous n\'avez pas accès à ce véhicule.']);
        } catch (TeslaApiException) {
            // The schedule push to the Tesla API failed.
            Flash::set('errors', ['La commande Tesla a échoué.']);
        }

        Http::redirect(self::PAGE);
    }

    // Updates the schedule identified by plan_id from the submitted form.
    public function update(): void
    {
        Csrf::requireValid(self::PAGE);
        [$userId, $vin] = $this->context();

        try {
            $this->service->updatePlan(
                $userId,
                new Vin($vin),
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
        } catch (TeslaApiException) {
            Flash::set('errors', ['La commande Tesla a échoué.']);
        }

        Http::redirect(self::PAGE);
    }

    // Deletes the schedule identified by plan_id (also removes it on the Tesla side).
    public function delete(): void
    {
        Csrf::requireValid(self::PAGE);
        [$userId, $vin] = $this->context();

        try {
            $this->service->deletePlan($userId, new Vin($vin), $this->post('plan_id'));
            Flash::set('success', 'Planification supprimée.');
        } catch (InvalidArgumentException) {
            Flash::set('errors', ['Saisie invalide.']);
        } catch (VehicleUnauthorizedException) {
            Flash::set('errors', ['Vous n\'avez pas accès à ce véhicule.']);
        } catch (TeslaApiException) {
            Flash::set('errors', ['La commande Tesla a échoué.']);
        }

        Http::redirect(self::PAGE);
    }

    // Enables or disables a schedule straight from the list (the list switch posts here).
    public function toggle(): void
    {
        Csrf::requireValid(self::PAGE);
        [$userId, $vin] = $this->context();

        try {
            $this->service->setPlanEnabled(
                $userId,
                new Vin($vin),
                $this->post('plan_id'),
                $this->boolField('enabled'),
            );
        } catch (InvalidArgumentException) {
            Flash::set('errors', ['Saisie invalide.']);
        } catch (VehicleUnauthorizedException) {
            Flash::set('errors', ['Vous n\'avez pas accès à ce véhicule.']);
        }
        // No TeslaApiException catch: toggling only writes our database, it never calls Tesla.

        Http::redirect(self::PAGE);
    }

    /**
     * Reads the user id and selected VIN from the session, redirecting out when either is
     * missing. The router does not enforce the auth flag, so each action guards itself here.
     *
     * @return array{string, string} the user id and the selected VIN
     */
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

    // Validates a "HH:MM" field so a malformed value never reaches the TIME column.
    private function readHour(string $field): string
    {
        $hour = $this->post($field);
        if (preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $hour) !== 1) {
            throw new InvalidArgumentException("Invalid {$field}");
        }

        return $hour;
    }

    // Optional window end: an empty field stays null (open-ended charging window).
    private function readOptionalHour(string $field): ?string
    {
        return $this->post($field) === '' ? null : $this->readHour($field);
    }

    /**
     * Maps the checked day boxes to a DayOfWeek list, dropping any unknown value.
     *
     * @return list<DayOfWeek>
     */
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

    // Optional geofence: both fields empty stays null, a partial or non-numeric pair is
    // rejected, and a valid pair builds a range-checked GeoPoint.
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

    // Trimmed POST string, or '' when the field is absent.
    private function post(string $name): string
    {
        $value = filter_input(INPUT_POST, $name, FILTER_DEFAULT);

        return is_string($value) ? trim($value) : '';
    }

    // Reads a checkbox: present and truthy means true, absent means false.
    private function boolField(string $name): bool
    {
        return filter_input(INPUT_POST, $name, FILTER_VALIDATE_BOOLEAN) === true;
    }
}
