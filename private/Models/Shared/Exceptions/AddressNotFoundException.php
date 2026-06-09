<?php

declare(strict_types=1);

namespace Teslapp\Models\Shared\Exceptions;

/**
 * Thrown when an address cannot be geocoded to coordinates.
 */
final class AddressNotFoundException extends TeslaAppException {}
