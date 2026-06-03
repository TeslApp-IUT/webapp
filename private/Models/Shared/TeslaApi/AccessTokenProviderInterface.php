<?php

declare(strict_types=1);

namespace Teslapp\Models\Shared\TeslaApi;

use Teslapp\Models\Shared\ValueObjects\AccessToken;

/**
 * Provides a valid Tesla access token for a given user.
 */
interface AccessTokenProviderInterface
{
    public function getValidAccessToken(string $userId): AccessToken;
}
