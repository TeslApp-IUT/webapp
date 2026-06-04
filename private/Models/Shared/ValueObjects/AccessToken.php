<?php

declare(strict_types=1);

namespace Teslapp\Models\Shared\ValueObjects;

use InvalidArgumentException;

/**
 * OAuth access token for Tesla API calls. No __toString() on purpose, to avoid
 * leaking it into logs.
 */
final readonly class AccessToken
{
    public string $value;

    /** @throws InvalidArgumentException if empty. */
    public function __construct(string $value)
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException('Access token cannot be empty.');
        }

        $this->value = $value;
    }
}
