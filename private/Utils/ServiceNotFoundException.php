<?php

declare(strict_types=1);

namespace Teslapp\Utils;

use RuntimeException;

/**
 * Exception thrown when a requested service does not exist in the container.
 */
class ServiceNotFoundException extends RuntimeException {}
