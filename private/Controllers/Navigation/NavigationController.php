<?php

declare(strict_types=1);

namespace Teslapp\Controllers\Navigation;

use InvalidArgumentException;
use Teslapp\Models\Navigation\NavigationRepositoryInterface;
use Teslapp\Models\Navigation\Trip;
use Teslapp\Models\Shared\Exceptions\VehicleUnauthorizedException;
use Teslapp\Models\Shared\Geocoding\GeocoderInterface;
use Teslapp\Models\Shared\ValueObjects\GeoPoint;
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
        private VehicleTelemetryRepository $telemetryRepository,
        private VehicleRepositoryInterface $vehicles,
        private NavigationRepositoryInterface $navigationRepository,
        private GeocoderInterface $geocoder,
    ) {}

    /**
     * GET dashboard/{vehicleId}/navigation
     * Displays the navigation page with the latest navigation data for the selected vehicle.
     **/
    public function page(): void
    {
        ['userId' => $userId, 'vin' => $vin, 'vehicleId' => $vehicleId] = $this->requireVehicle();

        try {
            $data = $this->telemetryRepository->getLatestTelemetry($vin);
        } catch (InvalidArgumentException | VehicleUnauthorizedException) {
            Flash::set('error', 'Véhicule invalide ou inaccessible.');
            Http::redirect('/dashboard');
        }

        $trips = $this->navigationRepository->listTrips($vin, null);

        $addresses = $this->resolveTripAddresses($trips);

        require_once __DIR__ . '/../../Views/Navigation/navigation.php';
    }

    /**
     * GET dashboard/{vehicleId}/navigation/trip?id={tripId}
     *
     * JSON endpoint backing the trip details card: returns the details of a
     * single trip so the navigation page can show more about it without a full
     * reload. Read-only GET, authenticated, scoped to the selected vehicle.
     */
    public function trip(): never
    {
        $userId = $_SESSION['user_id'] ?? '';
        if (!is_string($userId) || $userId === '') {
            Http::json(['error' => 'Authentication required'], 401);
        }

        $vehicle = $this->vehicles->findByPublicId(Route::param('vehicleId'));
        if ($vehicle === null || !$vehicle->isAccessibleBy($userId)) {
            Http::json(['error' => 'No vehicle selected'], 400);
        }

        $tripId = filter_input(INPUT_GET, 'id', FILTER_DEFAULT);
        $tripId = is_string($tripId) ? trim($tripId) : '';
        if (!$this->isUuid($tripId)) {
            Http::json(['error' => 'Invalid trip id'], 400);
        }

        $trip = $this->navigationRepository->findTrip($vehicle->vin, $tripId);
        if ($trip === null) {
            Http::json(['error' => 'Trip not found'], 404);
        }

        $trip = $trip->withPoints($this->navigationRepository->listTripPoints($trip));

        Http::json($this->tripDetails($trip));
    }

    /**
     * Shapes a trip into the JSON payload consumed by the details card. The
     * start/end points were already reverse-geocoded when the list rendered, so
     * these lookups are served from the cache rather than hitting Nominatim.
     *
     * @return array<string, mixed>
     */
    private function tripDetails(Trip $trip): array
    {
        $start = date_timestamp_get($trip->startTime);
        $end = date_timestamp_get($trip->endTime);

        // Prepare original raw points (lat, lon) for compatibility with older clients
        $points = array_map(
            static fn(GeoPoint $p): array => [$p->latitude, $p->longitude],
            $trip->points ?? [],
        );

        // Attempt to request a routed geometry from the OSRM backend when we have
        // at least two observed points. OSRM expects coordinates in lon,lat order
        // separated by semicolons.
        $route = null;
        if (count($trip->points ?? []) > 1) {
            $coords = array_map(
                static fn(GeoPoint $p): string => $p->longitude . ',' . $p->latitude,
                $trip->points,
            );
            $coordStr = implode(';', $coords);
            $osrmUrl =
                'https://osrm.feyli.dev/route/v1/driving/' .
                $coordStr .
                '?overview=full&geometries=geojson';

            $ch = curl_init($osrmUrl);
            if ($ch !== false) {
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 10,
                    CURLOPT_HTTPHEADER => ['User-Agent: Teslapp/1.0'],
                ]);

                $body = curl_exec($ch);
                $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if (is_string($body) && $status < 400) {
                    $data = json_decode($body, true);
                    if (
                        isset($data['routes'][0]['geometry']['coordinates']) &&
                        is_array($data['routes'][0]['geometry']['coordinates'])
                    ) {
                        // Convert OSRM geojson coordinates ([lon, lat]) to [lat, lon]
                        $route = array_map(
                            static fn(array $c): array => [(float) $c[1], (float) $c[0]],
                            $data['routes'][0]['geometry']['coordinates'],
                        );
                    }
                }
            }
        }

        return [
            'id' => $trip->id,
            'startAddress' => $this->geocoder->reverseGeocode($trip->start),
            'endAddress' => $this->geocoder->reverseGeocode($trip->end),
            'startLat' => $trip->start->latitude,
            'startLon' => $trip->start->longitude,
            'endLat' => $trip->end->latitude,
            'endLon' => $trip->end->longitude,
            'startTimestamp' => $start,
            'endTimestamp' => $end,
            'distanceKm' => round($trip->totalDistance / 1000, 1),
            'durationMinutes' => (int) round(($end - $start) / 60),
            'running' => $trip->running,
            'points' => $points,
            // 'route' is either null or an array of [lat, lon] coordinates derived
            // from OSRM's routed geometry (preferred for drawing a realistic route).
            'route' => $route,
        ];
    }

    private function isUuid(string $value): bool
    {
        return preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $value,
        ) === 1;
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
        $userId = (string) ($_SESSION['user_id'] ?? '');
        $vehicle = $this->vehicles->findByPublicId($vehicleId);
        if ($vehicle === null || !$vehicle->isAccessibleBy($userId)) {
            Http::redirect('/dashboard');
        }
        return ['userId' => $userId, 'vin' => $vehicle->vin];
    }
}
