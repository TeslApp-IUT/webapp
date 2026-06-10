<?php

declare(strict_types=1);

namespace Teslapp\Models\Shared\Geocoding;

/**
 * The two forms of a reverse-geocoded address: a short label for compact lists
 * ("Avenue Gaston Berger, Aix-en-Provence") and the full address for detailed
 * views (Nominatim's complete display name).
 */
final readonly class ReverseGeocodeResult
{
    public function __construct(
        public string $short,
        public string $full,
    ) {}
}
