<?php

declare(strict_types=1);

namespace Teslapp\Tests\Unit\Models\Charging\ValueObjects;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Teslapp\Models\Charging\ValueObjects\ChargingAmps;

#[CoversClass(ChargingAmps::class)]
final class ChargingAmpsTest extends TestCase
{
    #[Test]
    public function acceptsAValueInsideTheRange(): void
    {
        self::assertSame(16, (new ChargingAmps(16))->value);
    }

    #[Test]
    public function acceptsTheBounds(): void
    {
        self::assertSame(5, (new ChargingAmps(5))->value);
        self::assertSame(48, (new ChargingAmps(48))->value);
    }

    #[Test]
    #[DataProvider('outOfRangeValues')]
    public function rejectsAValueOutOfRange(int $value): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ChargingAmps($value);
    }

    /** @return array<string, array{int}> */
    public static function outOfRangeValues(): array
    {
        return [
            'just below minimum' => [4],
            'just above maximum' => [49],
            'zero' => [0],
            'negative' => [-16],
        ];
    }
}
