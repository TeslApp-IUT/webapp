<?php

declare(strict_types=1);

namespace Teslapp\Models\Shared\Exceptions;

/**
 * Thrown when a Tesla API call fails (network, auth, or API error).
 */
class TeslaApiException extends TeslaAppException {}
