<?php

declare(strict_types=1);

namespace Teslapp\Models\Shared\Geocoding;

use Teslapp\Models\Shared\ValueObjects\GeoPoint;

/**
 * Resolves an address to coordinates and back, to pick a schedule's geofence.
 */
interface GeocoderInterface
{
    /** @return GeocodeResult|null  null when no address matches */
    public function geocode(string $address): ?GeocodeResult;

    /** @return string|null  readable address of the point, null if none found */
    public function reverseGeocode(GeoPoint $point): ?string;
}
