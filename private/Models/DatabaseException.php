<?php

declare(strict_types=1);

namespace Teslapp\Models;

use RuntimeException;

/**
 * Exception thrown when a critical error occurs with the database.
 */
class DatabaseException extends RuntimeException {}
