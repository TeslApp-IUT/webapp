<?php

declare(strict_types=1);

namespace Teslapp\Tests\Unit\Models\Shared\ValueObjects;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Teslapp\Models\Shared\ValueObjects\GeoPoint;

#[CoversClass(GeoPoint::class)]
final class GeoPointTest extends TestCase
{
    #[Test]
    public function acceptsValidCoordinates(): void
    {
        $point = new GeoPoint(43.2965, 5.3698);

        self::assertSame(43.2965, $point->latitude);
        self::assertSame(5.3698, $point->longitude);
    }

    #[Test]
    public function acceptsTheExtremeBounds(): void
    {
        $point = new GeoPoint(-90.0, 180.0);

        self::assertSame(-90.0, $point->latitude);
        self::assertSame(180.0, $point->longitude);
    }

    #[Test]
    #[DataProvider('outOfRangeCoordinates')]
    public function rejectsCoordinatesOutOfRange(float $latitude, float $longitude): void
    {
        $this->expectException(InvalidArgumentException::class);

        new GeoPoint($latitude, $longitude);
    }

    /** @return array<string, array{float, float}> */
    public static function outOfRangeCoordinates(): array
    {
        return [
            'latitude too low' => [-90.01, 0.0],
            'latitude too high' => [90.01, 0.0],
            'longitude too low' => [0.0, -180.01],
            'longitude too high' => [0.0, 180.01],
        ];
    }
}
