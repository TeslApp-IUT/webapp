<?php

declare(strict_types=1);

namespace Teslapp\Tests\Unit\Models\Vehicle;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Teslapp\Models\Shared\ValueObjects\Vin;
use Teslapp\Models\Vehicle\Vehicle;

#[CoversClass(Vehicle::class)]
final class VehicleTest extends TestCase
{
    #[Test]
    public function isAccessibleOnlyByItsOwner(): void
    {
        $vehicle = new Vehicle(new Vin('5YJ3E1EA7KF000316'), 'user-1', 'Ma Tesla', 'model-3-id');

        self::assertTrue($vehicle->isAccessibleBy('user-1'));
        self::assertFalse($vehicle->isAccessibleBy('user-2'));
    }

    #[Test]
    public function buildsFromADatabaseRow(): void
    {
        $vehicle = Vehicle::fromRow([
            'vin' => '5YJ3E1EA7KF000316',
            'user_id' => 'user-1',
            'name' => 'Ma Tesla',
            'model_id' => 'model-3-id',
        ]);

        self::assertSame('5YJ3E1EA7KF000316', $vehicle->vin->value);
        self::assertSame('user-1', $vehicle->userId);
        self::assertSame('Ma Tesla', $vehicle->name);
        self::assertSame('model-3-id', $vehicle->modelId);
    }

    #[Test]
    public function buildsFromATeslaResponseWithoutOwnerOrModel(): void
    {
        $vehicle = Vehicle::fromTeslaResponse([
            'vin' => '5YJ3E1EA7KF000316',
            'display_name' => 'Owned',
        ]);

        self::assertSame('5YJ3E1EA7KF000316', $vehicle->vin->value);
        self::assertSame('Owned', $vehicle->name);
        self::assertSame('', $vehicle->userId);
        self::assertSame('', $vehicle->modelId);
    }
}
