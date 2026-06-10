<?php

declare(strict_types=1);

namespace Teslapp\Models\Shared\Geocoding;

use PDO;
use Teslapp\Models\Shared\ValueObjects\GeoPoint;

/**
 * Decorates a geocoder with a persistent reverse-geocoding cache
 * ({@see app.geocode_cache}): a coordinate is resolved against the wrapped
 * geocoder once, then served from the database on every later lookup.
 *
 * Forward geocoding is delegated unchanged; only reverse lookups are cached.
 */
final readonly class CachingGeocoder implements GeocoderInterface
{
    public function __construct(
        private GeocoderInterface $inner,
        private PDO $pdo,
    ) {}

    public function geocode(string $address): ?GeocodeResult
    {
        return $this->inner->geocode($address);
    }

    public function reverseGeocode(GeoPoint $point): ?string
    {
        // Match the NUMERIC(8,6)/(9,6) precision of the cache columns so the
        // lookup key is identical to what gets stored.
        $lat = sprintf('%.6f', $point->latitude);
        $lon = sprintf('%.6f', $point->longitude);

        $cached = $this->lookup($lat, $lon);
        if ($cached !== null) {
            return $cached;
        }

        $label = $this->inner->reverseGeocode($point);
        if ($label !== null) {
            $this->store($lat, $lon, $label);
        }

        return $label;
    }

    private function lookup(string $lat, string $lon): ?string
    {
        $stmt = $this->pdo->prepare('
            SELECT label
            FROM app.geocode_cache
            WHERE latitude = :lat AND longitude = :lon
        ');
        $stmt->bindValue(':lat', $lat);
        $stmt->bindValue(':lon', $lon);
        $stmt->execute();

        $label = $stmt->fetchColumn();

        return is_string($label) ? $label : null;
    }

    private function store(string $lat, string $lon, string $label): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO app.geocode_cache (latitude, longitude, label)
            VALUES (:lat, :lon, :label)
            ON CONFLICT (latitude, longitude) DO NOTHING
        ');
        $stmt->bindValue(':lat', $lat);
        $stmt->bindValue(':lon', $lon);
        $stmt->bindValue(':label', $label);
        $stmt->execute();
    }
}
