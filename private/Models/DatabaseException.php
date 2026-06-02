<?php

declare(strict_types=1);

namespace Teslapp\Models;

use RuntimeException;

/**
 * Exception levée lorsqu'une erreur critique survient avec la base de données.
 */
class DatabaseException extends RuntimeException {}
