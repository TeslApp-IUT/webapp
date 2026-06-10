<?php

declare(strict_types=1);

namespace Teslapp\Tests\Unit\Models\Charging\ValueObjects;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Teslapp\Models\Charging\ValueObjects\ChargeLimit;

#[CoversClass(ChargeLimit::class)]
final class ChargeLimitTest extends TestCase
{
    #[Test]
    public function acceptsAValueInsideTheRange(): void
    {
        self::assertSame(80, (new ChargeLimit(80))->value);
    }

    #[Test]
    public function acceptsTheBounds(): void
    {
        self::assertSame(50, (new ChargeLimit(50))->value);
        self::assertSame(100, (new ChargeLimit(100))->value);
    }

    #[Test]
    #[DataProvider('outOfRangeValues')]
    public function rejectsAValueOutOfRange(int $value): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ChargeLimit($value);
    }

    /** @return array<string, array{int}> */
    public static function outOfRangeValues(): array
    {
        return [
            'just below minimum' => [49],
            'just above maximum' => [101],
            'zero' => [0],
            'negative' => [-10],
        ];
    }
}
