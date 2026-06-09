<?php

declare(strict_types=1);

namespace Teslapp\Controllers;

use InvalidArgumentException;
use Teslapp\Models\Shared\Geocoding\GeocoderInterface;
use Teslapp\Models\Shared\ValueObjects\GeoPoint;
use Teslapp\Utils\Http;

/**
 * JSON geocoding endpoints for picking a schedule's location: forward
 * (address to coordinates) and reverse (coordinates to address). Requires
 * login so the app is not an open geocoding proxy; read-only GET, no CSRF.
 */
final class GeocodingController
{
    public function __construct(private readonly GeocoderInterface $geocoder) {}

    public function geocode(): never
    {
        $this->requireAuthenticated();

        $query = filter_input(INPUT_GET, 'q', FILTER_DEFAULT);
        $query = is_string($query) ? trim($query) : '';
        if ($query === '') {
            Http::json(['error' => 'Missing address query'], 400);
        }

        $result = $this->geocoder->geocode($query);
        if ($result === null) {
            Http::json(['error' => 'Address not found'], 404);
        }

        Http::json([
            'lat' => $result->point->latitude,
            'lon' => $result->point->longitude,
            'label' => $result->label,
        ]);
    }

    public function reverse(): never
    {
        $this->requireAuthenticated();

        $lat = filter_input(INPUT_GET, 'lat', FILTER_VALIDATE_FLOAT);
        $lon = filter_input(INPUT_GET, 'lon', FILTER_VALIDATE_FLOAT);
        if (!is_float($lat) || !is_float($lon)) {
            Http::json(['error' => 'Invalid coordinates'], 400);
        }

        try {
            $point = new GeoPoint($lat, $lon);
        } catch (InvalidArgumentException) {
            Http::json(['error' => 'Coordinates out of range'], 400);
        }

        $label = $this->geocoder->reverseGeocode($point);
        if ($label === null) {
            Http::json(['error' => 'No address found'], 404);
        }

        Http::json(['label' => $label]);
    }

    private function requireAuthenticated(): void
    {
        if (
            !isset($_SESSION['user_id']) ||
            !is_string($_SESSION['user_id']) ||
            $_SESSION['user_id'] === ''
        ) {
            Http::json(['error' => 'Authentication required'], 401);
        }
    }
}
