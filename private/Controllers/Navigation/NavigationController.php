<?php

declare(strict_types=1);

namespace Teslapp\Controllers\Navigation;

use InvalidArgumentException;
use Teslapp\Models\Navigation\NavigationRepositoryInterface;
use Teslapp\Models\Shared\Exceptions\VehicleUnauthorizedException;
use Teslapp\Models\Shared\Geocoding\GeocoderInterface;
use Teslapp\Models\Shared\ValueObjects\Vin;
use Teslapp\Models\Shared\VehicleTelemetryRepository;
use Teslapp\Models\Vehicle\VehicleRepositoryInterface;
use Teslapp\Utils\Flash;
use Teslapp\Utils\Http;
use Teslapp\Utils\Route;

/**
 * Controller for the navigation page.
 * Displays navigation-related information for the selected vehicle.
 */
final readonly class NavigationController
{
    public function __construct(
        private VehicleTelemetryRepository    $telemetryRepository,
        private VehicleRepositoryInterface    $vehicles,
        private NavigationRepositoryInterface $navigationRepository,
        private GeocoderInterface             $geocoder
    )
    {
    }

    /**
     * GET dashboard/{vehicleId}/navigation
     * Displays the navigation page with the latest navigation data for the selected vehicle.
     **/
    public function page(): void
    {
        ['userId' => $userId, 'vin' => $vin, 'vehicleId' => $vehicleId] = $this->requireVehicle();

        try {
            $data = $this->telemetryRepository->getLatestTelemetry($vin);
        } catch (InvalidArgumentException|VehicleUnauthorizedException) {
            Flash::set('error', 'Véhicule invalide ou inaccessible.');
            Http::redirect('/dashboard');
        }

        $trips = $this->navigationRepository->listTrips($vin, null);

        $addresses = $this->resolveTripAddresses($trips);

        require_once __DIR__ . '/../../Views/Navigation/navigation.php';
    }

    /**
     * Reverse-geocodes the start and end point of each trip into a readable
     * address, keyed by trip id.
     *
     * @param list<\Teslapp\Models\Navigation\Trip> $trips
     * @return array<string, array{start: ?string, end: ?string}>
     */
    private function resolveTripAddresses(array $trips): array
    {
        $addresses = [];
        foreach ($trips as $trip) {
            $addresses[$trip->id] = [
                'start' => $this->geocoder->reverseGeocode($trip->start),
                'end' => $this->geocoder->reverseGeocode($trip->end),
            ];
        }

        return $addresses;
    }

    /**
     * Resolves the vehicle targeted by the {vehicleId} route parameter.
     *
     * @return array{userId: string, vin: Vin, vehicleId: string}
     */
    private function requireVehicle(): array
    {
        $vehicleId = Route::param('vehicleId');
        return $this->resolveVehicle($vehicleId) + ['vehicleId' => $vehicleId];
    }

    /**
     * @return array{userId: string, vin: Vin}
     */
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
