<?php

declare(strict_types=1);

namespace Teslapp\Tests\Unit\Models\Shared\ValueObjects;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Teslapp\Models\Shared\ValueObjects\Vin;

#[CoversClass(Vin::class)]
final class VinTest extends TestCase
{
    #[Test]
    public function acceptsAValidVin(): void
    {
        $vin = new Vin('5YJ3E1EA7KF000316');

        self::assertSame('5YJ3E1EA7KF000316', $vin->value);
        self::assertSame('5YJ3E1EA7KF000316', (string) $vin);
    }

    #[Test]
    public function normalizesLowercaseAndSurroundingWhitespace(): void
    {
        $vin = new Vin('  5yj3e1ea7kf000316  ');

        self::assertSame('5YJ3E1EA7KF000316', $vin->value);
    }

    #[Test]
    #[DataProvider('invalidVins')]
    public function rejectsAnInvalidVin(string $invalid): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Vin($invalid);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidVins(): array
    {
        return [
            'too short' => ['5YJ3E1EA7KF00031'],
            'too long' => ['5YJ3E1EA7KF0003160'],
            'empty' => [''],
            'forbidden letter I' => ['5YJ3E1EA7KI000316'],
            'forbidden letter O' => ['5YJ3E1EA7KO000316'],
            'forbidden letter Q' => ['5YJ3E1EA7KQ000316'],
            'forbidden character' => ['5YJ3E1EA7KF-00316'],
        ];
    }
}
