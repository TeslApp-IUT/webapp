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

        $fallback =
            isset($hit['display_name']) && is_string($hit['display_name'])
                ? $hit['display_name']
                : $address;
        $label = $this->compactLabel($hit['address'] ?? null, $fallback);

        return new GeocodeResult(new GeoPoint((float) $hit['lat'], (float) $hit['lon']), $label);
    }

    public function reverseGeocode(GeoPoint $point): ?string
    {
        $data = $this->get('/reverse', [
            'lat' => $point->latitude,
            'lon' => $point->longitude,
            'format' => 'jsonv2',
            // Building-level precision, with the address split into fields
            // so a short "number street, postcode city" label can be built.
            'zoom' => 18,
            'addressdetails' => 1,
        ]);

        if (!isset($data['display_name']) || !is_string($data['display_name'])) {
            return null;
        }

        return $this->compactLabel($data['address'] ?? null, $data['display_name']);
    }

    /**
     * Short human label ("413 Avenue Gaston Berger, 13090 Aix-en-Provence") built
     * from Nominatim address details; falls back to the verbose display name.
     *
     * @param mixed $address the `address` object of a Nominatim response
     */
    private function compactLabel(mixed $address, string $fallback): string
    {
        if (!is_array($address)) {
            return $fallback;
        }

        $part = static fn(string $key): ?string => isset($address[$key]) &&
        is_string($address[$key]) &&
        $address[$key] !== ''
            ? $address[$key]
            : null;

        $street = trim(
            ($part('house_number') ?? '') . ' ' . ($part('road') ?? ($part('pedestrian') ?? '')),
        );
        $city = $part('city') ?? ($part('town') ?? ($part('village') ?? $part('municipality')));
        $cityLine = trim(($part('postcode') ?? '') . ' ' . ($city ?? ''));

        $label = implode(
            ', ',
            array_filter([$street, $cityLine], static fn(string $s): bool => $s !== ''),
        );

        return $label !== '' ? $label : $fallback;
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
