<?php

declare(strict_types=1);

namespace Teslapp\Tests\Unit\Models\Vehicle;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Teslapp\Models\Shared\Exceptions\VehicleUnauthorizedException;
use Teslapp\Models\Shared\TeslaApi\VehicleCommandClient;
use Teslapp\Models\Shared\ValueObjects\AccessToken;
use Teslapp\Models\Shared\ValueObjects\Vin;
use Teslapp\Models\Vehicle\VehicleCommandService;
use Teslapp\Models\Vehicle\VehicleRepositoryInterface;

#[CoversClass(VehicleCommandService::class)]
final class VehicleCommandServiceTest extends TestCase
{
    private const VIN = '5YJ3E1EA7KF000316';

    #[Test]
    public function lockSendsTheCommandWhenTheUserOwnsTheVehicle(): void
    {
        $vin = new Vin(self::VIN);
        $token = new AccessToken('tok');

        $commands = $this->createMock(VehicleCommandClient::class);
        $commands->expects($this->once())->method('lock')->with($token, $vin);

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->with($vin, 'user-1')->willReturn(true);

        (new VehicleCommandService($commands, $vehicles))->lock('user-1', $vin, $token);
    }

    #[Test]
    public function lockThrowsWhenTheUserDoesNotOwnTheVehicle(): void
    {
        $vin = new Vin(self::VIN);
        $token = new AccessToken('tok');

        $commands = $this->createMock(VehicleCommandClient::class);
        $commands->expects($this->never())->method('lock');

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->willReturn(false);

        $service = new VehicleCommandService($commands, $vehicles);

        $this->expectException(VehicleUnauthorizedException::class);
        $service->lock('user-2', $vin, $token);
    }

    #[Test]
    public function unlockSendsTheCommandWhenTheUserOwnsTheVehicle(): void
    {
        $vin = new Vin(self::VIN);
        $token = new AccessToken('tok');

        $commands = $this->createMock(VehicleCommandClient::class);
        $commands->expects($this->once())->method('unlock')->with($token, $vin);

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->with($vin, 'user-1')->willReturn(true);

        (new VehicleCommandService($commands, $vehicles))->unlock('user-1', $vin, $token);
    }

    #[Test]
    public function unlockThrowsWhenTheUserDoesNotOwnTheVehicle(): void
    {
        $vin = new Vin(self::VIN);
        $token = new AccessToken('tok');

        $commands = $this->createMock(VehicleCommandClient::class);
        $commands->expects($this->never())->method('unlock');

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->willReturn(false);

        $service = new VehicleCommandService($commands, $vehicles);

        $this->expectException(VehicleUnauthorizedException::class);
        $service->unlock('user-2', $vin, $token);
    }
}
