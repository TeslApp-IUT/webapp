<?php

declare(strict_types=1);

namespace Teslapp\Models\Shared\TeslaApi;

use Teslapp\Models\Shared\Exceptions\TeslaApiException;
use Teslapp\Models\Shared\ValueObjects\AccessToken;
use Teslapp\Models\Vehicle\Vehicle;

/**
 * Read access to vehicle data from the Tesla API.
 */
interface VehicleStateClient
{
    /**
     * Lists the vehicles accessible with the given token.
     *
     * @return Vehicle[]
     *
     * @throws TeslaApiException on a network or API error.
     */
    public function listVehicles(AccessToken $token): array;
}
