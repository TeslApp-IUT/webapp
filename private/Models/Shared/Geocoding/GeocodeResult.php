<?php

declare(strict_types=1);

namespace Teslapp\Models\Shared\Geocoding;

use Teslapp\Models\Shared\ValueObjects\GeoPoint;

/**
 * A geocoded location: a point and its address label.
 */
final readonly class GeocodeResult
{
    public function __construct(public GeoPoint $point, public string $label) {}
}
