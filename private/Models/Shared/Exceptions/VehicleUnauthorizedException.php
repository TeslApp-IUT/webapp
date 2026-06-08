<?php

declare(strict_types=1);

namespace Teslapp\Models\Shared\Exceptions;

/**
 * Thrown when a user tries to act on a vehicle (VIN) they do not own.
 */
final class VehicleUnauthorizedException extends TeslaAppException {}
