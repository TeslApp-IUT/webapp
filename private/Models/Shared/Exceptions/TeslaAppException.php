<?php

declare(strict_types=1);

namespace Teslapp\Models\Shared\Exceptions;

use RuntimeException;

/**
 * Base class for every Tesla App domain exception.
 */
abstract class TeslaAppException extends RuntimeException {}
