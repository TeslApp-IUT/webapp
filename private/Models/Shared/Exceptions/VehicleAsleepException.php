<?php

declare(strict_types=1);

namespace Teslapp\Models\Shared\Exceptions;

/**
 * Thrown when Tesla rejects a request because the vehicle is offline or asleep
 * (Fleet API HTTP 408 "vehicle unavailable"). Command services catch it to wake
 * the vehicle and retry the command.
 */
final class VehicleAsleepException extends TeslaApiException {}
