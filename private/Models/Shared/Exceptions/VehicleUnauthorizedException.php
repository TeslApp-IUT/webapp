<?php

declare(strict_types=1);

namespace Teslapp\Models\Shared\Exceptions;

/**
 * Thrown when a user tries to act on a vehicle they do not own.
 */
final class VehicleUnauthorizedException extends TeslaAppException
{
    public function __construct(string $vin, string $userId)
    {
        parent::__construct("User {$userId} is not allowed to access vehicle {$vin}");
    }
}
