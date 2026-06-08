<?php

declare(strict_types=1);

namespace Teslapp\Models\Shared\Geocoding;

use Teslapp\Models\Shared\ValueObjects\GeoPoint;

/**
 * Geocoder backed by the OpenStreetMap Nominatim API.
 * Base URL and User-Agent come from configuration.
 */
final readonly class NominatimGeocoder implements GeocoderInterface
{
    public function __construct(
        private string $baseUrl,
        private string $userAgent,
        private int $timeoutSeconds = 5,
    ) {}

    public function geocode(string $address): ?GeocodeResult
    {
        $data = $this->get('/search', [
            'q' => $address,
            'format' => 'jsonv2',
            'limit' => 1,
            'addressdetails' => 1,
        ]);

        $hit = $data[0] ?? null;
        if (!is_array($hit) || !isset($hit['lat'], $hit['lon'])) {
            return null;
        }

        $label =
            isset($hit['display_name']) && is_string($hit['display_name'])
                ? $hit['display_name']
                : $address;

        return new GeocodeResult(new GeoPoint((float) $hit['lat'], (float) $hit['lon']), $label);
    }

    public function reverseGeocode(GeoPoint $point): ?string
    {
        $data = $this->get('/reverse', [
            'lat' => $point->latitude,
            'lon' => $point->longitude,
            'format' => 'jsonv2',
        ]);

        return isset($data['display_name']) && is_string($data['display_name'])
            ? $data['display_name']
            : null;
    }

    /**
     * @param array<string, scalar> $params
     * @return array<mixed> decoded JSON, or [] on error
     */
    private function get(string $path, array $params): array
    {
        $handle = curl_init($this->baseUrl . $path . '?' . http_build_query($params));
        if ($handle === false) {
            return [];
        }

        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
            CURLOPT_HTTPHEADER => ['User-Agent: ' . $this->userAgent],
        ]);

        $body = curl_exec($handle);
        $status = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);

        if (!is_string($body) || $status >= 400) {
            return [];
        }

        $decoded = json_decode($body, true);

        return is_array($decoded) ? $decoded : [];
    }
}
