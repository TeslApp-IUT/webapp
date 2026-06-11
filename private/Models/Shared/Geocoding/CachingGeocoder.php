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
    public function __construct(private GeocoderInterface $inner, private PDO $pdo) {}

    public function geocode(string $address): ?GeocodeResult
    {
        return $this->inner->geocode($address);
    }

    public function reverseGeocode(GeoPoint $point): ?ReverseGeocodeResult
    {
        // Match the NUMERIC(8,6)/(9,6) precision of the cache columns so the
        // lookup key is identical to what gets stored.
        $lat = sprintf('%.6f', $point->latitude);
        $lon = sprintf('%.6f', $point->longitude);

        $cached = $this->lookup($lat, $lon);
        if ($cached !== null) {
            return $cached;
        }

        $result = $this->inner->reverseGeocode($point);
        if ($result !== null) {
            $this->store($lat, $lon, $result);
        }

        return $result;
    }

    private function lookup(string $lat, string $lon): ?ReverseGeocodeResult
    {
        $stmt = $this->pdo->prepare('
            SELECT label, full_address
            FROM app.geocode_cache
            WHERE latitude = :lat AND longitude = :lon
        ');
        $stmt->bindValue(':lat', $lat);
        $stmt->bindValue(':lon', $lon);
        $stmt->execute();

        $row = $stmt->fetch();

        // Rows written before the full_address column existed have it NULL; treat
        // those as a miss so the next lookup refetches and backfills the address.
        if (!is_array($row) || !is_string($row['label']) || !is_string($row['full_address'])) {
            return null;
        }

        return new ReverseGeocodeResult($row['label'], $row['full_address']);
    }

    private function store(string $lat, string $lon, ReverseGeocodeResult $result): void
    {
        // Upsert rather than DO NOTHING so a pre-migration row (short label only)
        // gets its full address filled in on the next resolution.
        $stmt = $this->pdo->prepare('
            INSERT INTO app.geocode_cache (latitude, longitude, label, full_address)
            VALUES (:lat, :lon, :label, :full)
            ON CONFLICT (latitude, longitude)
            DO UPDATE SET label = EXCLUDED.label, full_address = EXCLUDED.full_address
        ');
        $stmt->bindValue(':lat', $lat);
        $stmt->bindValue(':lon', $lon);
        $stmt->bindValue(':label', $result->short);
        $stmt->bindValue(':full', $result->full);
        $stmt->execute();
    }
}
