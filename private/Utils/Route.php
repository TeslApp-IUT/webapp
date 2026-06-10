<?php

declare(strict_types=1);

namespace Teslapp\Utils;

/**
 * Holds URL parameters extracted from parameterized route patterns during routing.
 * Controllers call Route::param() to read values like {vehicleId} from the URL.
 */
final class Route
{
    /** @var array<string, string> */
    private static array $params = [];

    /** @param array<string, string> $params */
    public static function setParams(array $params): void
    {
        self::$params = $params;
    }

    public static function param(string $name): string
    {
        return self::$params[$name] ?? '';
    }
}
